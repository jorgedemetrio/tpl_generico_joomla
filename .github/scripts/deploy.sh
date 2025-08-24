#!/bin/bash

# Sair imediatamente se um comando sair com um status diferente de zero.
set -e

# --- Configuração ---
# Nome da aplicação, usado para nomear arquivos e diretórios.
APP_NAME="tpl_generico"

# Diretório de origem que contém os arquivos do template.
SOURCE_DIR="tpl_generico"

# Diretório de build temporário.
BUILD_DIR="build_temp"

# --- Validação ---
# Verifica se a versão foi passada como argumento.
if [ -z "$1" ]; then
  echo "Erro: A versão não foi fornecida."
  echo "Uso: $0 <versao>"
  exit 1
fi
VERSION=$1

# Remove o prefixo 'v' se existir (ex: v1.2.3 -> 1.2.3).
PLAIN_VERSION=${VERSION#v}

# Nome do arquivo ZIP final.
ZIP_FILE="${APP_NAME}-${PLAIN_VERSION}.zip"

# --- Logging ---
echo "Iniciando o processo de deploy para ${APP_NAME}, versão ${PLAIN_VERSION}"

# --- Limpeza e Preparação ---
echo "Limpando diretórios de build anteriores..."
rm -rf ${BUILD_DIR}
mkdir -p ${BUILD_DIR}

echo "Copiando arquivos do template para o diretório de build..."
cp -r ${SOURCE_DIR}/* ${BUILD_DIR}/

# --- Atualização da Versão no XML ---
echo "Atualizando a versão no templateDetails.xml para ${PLAIN_VERSION}..."
sed -i "s|<version>.*</version>|<version>${PLAIN_VERSION}</version>|g" "${BUILD_DIR}/templateDetails.xml"
echo "Versão atualizada com sucesso."
echo "Atualizando a versão no joomla.asset.json para ${PLAIN_VERSION}..."
sed -i "s|\"version\":*,|\"version\": \"${PLAIN_VERSION}</version>|g" "${BUILD_DIR}/joomla.asset.json"
echo "Versão atualizada com sucesso."
  


# --- Geração do Pacote ZIP ---
echo "Criando o pacote de instalação: ${ZIP_FILE}"
cd ${BUILD_DIR}
zip -r ../${ZIP_FILE} .
cd ..
echo "Pacote ZIP criado com sucesso em $(pwd)/${ZIP_FILE}"

# --- Geração da Entrada de Atualização ---
echo "Gerando a nova entrada de atualização..."
cat > nova_entrada.xml << EOL
    <update>
        <name>${APP_NAME}</name>
        <description>Template Genérico para Joomla 5</description>
        <element>generico</element>
        <type>template</type>
        <version>${PLAIN_VERSION}</version>
        <infourl title="Sobieski Produções">https://apps.sobieskiproducoes.com.br/${APP_NAME}/atualizacao.xml</infourl>
        <downloads>
            <downloadurl type="full" format="zip">https://apps.sobieskiproducoes.com.br/${APP_NAME}/${ZIP_FILE}</downloadurl>
        </downloads>
        <tags>
            <tag>stable</tag>
        </tags>
        <maintainer>Jorge Demetrio</maintainer>
        <maintainerurl>https://www.sobieskiproducoes.com.br</maintainerurl>
        <targetplatform name="joomla" version="5.*"/>
        <php_minimum>8.1</php_minimum>
    </update>
EOL
echo "Nova entrada de atualização gerada em nova_entrada.xml."

# --- Combinação do XML de Atualização ---
echo "Preparando o arquivo atualizacao.xml final..."

# Tenta baixar o arquivo de atualização existente via wget.
FILE_EXISTS=true
echo "Tentando baixar o arquivo atualizacao.xml existente de https://apps.sobieskiproducoes.com.br/${APP_NAME}/atualizacao.xml..."
wget -O atualizacao_remota.xml "https://apps.sobieskiproducoes.com.br/${APP_NAME}/atualizacao.xml" > /dev/null 2>&1 || FILE_EXISTS=false

if [ "$FILE_EXISTS" = false ]; then
  echo "Arquivo atualizacao.xml não encontrado no servidor. Criando um novo."
  (
    echo '<?xml version="1.0" encoding="utf-8"?>'
    echo '<updates>'
    cat nova_entrada.xml
    echo '</updates>'
  ) > atualizacao.xml
else
  echo "Arquivo atualizacao.xml encontrado. Adicionando nova entrada."
  sed '2r nova_entrada.xml' atualizacao_remota.xml > atualizacao.xml
fi
echo "Arquivo atualizacao.xml final gerado com sucesso."


# --- Deploy via SFTP ---
echo "Iniciando o deploy para o servidor SFTP..."

# Verifica se as variáveis de ambiente de FTP estão configuradas.
if [ -z "$FTP_URL" ] || [ -z "$FTP_USER" ] || [ -z "$FTP_PASSWORD" ]; then
    echo "Erro: As variáveis de ambiente FTP_URL, FTP_USER e FTP_PASSWORD devem ser configuradas."
    exit 1
fi

# Usa lftp para o upload.
lftp -c "set sftp:auto-confirm yes; set ftp:ssl-allow yes; set ssl:verify-certificate no;
open -u ${FTP_USER},${FTP_PASSWORD} sftp://${FTP_URL};
mkdir -p /${APP_NAME};
cd /${APP_NAME};
put -O . ${ZIP_FILE};
put -O . atualizacao.xml;
bye"

echo "Deploy concluído com sucesso!"

# --- Limpeza Final ---
echo "Limpando arquivos temporários..."
rm -rf ${BUILD_DIR}
rm ${ZIP_FILE}
rm nova_entrada.xml
rm -f atualizacao.xml atualizacao_remota.xml

echo "Processo finalizado."
