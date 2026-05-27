FROM php:8.1-apache

# Instala dependências do SQLite, PostgreSQL e habilita as extensões
RUN apt-get update && apt-get install -y libsqlite3-dev libpq-dev && docker-php-ext-install pdo pdo_sqlite pdo_pgsql pgsql

# Copia todos os arquivos PHP para dentro do servidor web
COPY . /var/www/html/

# Dá permissão de escrita para o banco de dados SQLite
RUN chmod -R 777 /var/www/html/

# Expõe a porta 80
EXPOSE 80
