# Usa a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Permite .htaccess (rotas amigáveis) e ativa rewrite
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && a2enmod rewrite

# Instala extensões necessárias, incluindo MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copia os arquivos do projeto para o servidor web dentro do container
COPY src/ /var/www/html/

# Define permissões para o Apache rodar os arquivos
RUN chown -R www-data:www-data /var/www/html

# Exibe as mensagens de erro do PHP
ENV PHP_DISPLAY_ERRORS=On

# Define a porta que o Apache vai escutar
EXPOSE 80
