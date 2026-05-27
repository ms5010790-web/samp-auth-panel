FROM php:8.1-apache

# Instala dependências do SQLite e habilita a extensão
RUN apt-get update && apt-get install -y libsqlite3-dev && docker-php-ext-install pdo pdo_sqlite

# Copia todos os arquivos PHP para dentro do servidor web
COPY . /var/www/html/

# Dá permissão de escrita para o banco de dados SQLite
RUN chmod -R 777 /var/www/html/

# Expõe a porta 80
EXPOSE 80
