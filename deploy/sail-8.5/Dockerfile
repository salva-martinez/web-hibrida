# Runtime de Sail propio para FisioApp.
#
# Es una copia de vendor/laravel/sail/runtimes/8.5/Dockerfile con dos cambios:
#
#   1. El repositorio de PHP es packages.sury.org en lugar del PPA de
#      Launchpad. El PPA va con retraso (tarda horas o días en publicar un
#      parche de seguridad) y además devuelve errores 503 con facilidad.
#      packages.sury.org publica el mismo día y es el repositorio que
#      recomienda php.net para Ubuntu.
#
#   2. La instalación de PHP está en su propia capa (ARG PHP_REFRESH) para que
#      un despliegue pueda re-ejecutarla y traerse el último parche sin
#      reconstruir la imagen entera (ffmpeg, playwright, etc. tardan mucho).
#
# Por qué vive aquí y no en vendor/: `composer install` borra vendor/ y el
# Dockerfile de Sail cambia con cada versión de Sail. Aquí el runtime es
# estable y controlable, y el despliegue puede exigir una versión mínima.
FROM ubuntu:24.04

LABEL maintainer="Taylor Otwell"

ARG WWWGROUP
ARG NODE_VERSION=24
ARG MYSQL_CLIENT="mysql-client"
ARG POSTGRES_VERSION=18

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV LANG=C.UTF-8
ENV SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER="sail"
ENV PLAYWRIGHT_BROWSERS_PATH=0

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::BrokenProxy    true;" >> /etc/apt/apt.conf.d/99custom

# --- Capa 1: sistema base, node y postgres ---------------------------------
RUN apt-get update && apt-get upgrade -y \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python3 dnsutils librsvg2-bin fswatch ffmpeg nano \
    && curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb \
    && dpkg -i /tmp/debsuryorg-archive-keyring.deb \
    && echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php noble main" > /etc/apt/sources.list.d/php.list \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_VERSION.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y nodejs \
    && npm install -g npm pnpm bun corepack \
    && corepack enable \
    && corepack prepare yarn@stable --activate \
    && npx -y playwright install-deps \
    && curl -sS https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor | tee /etc/apt/keyrings/pgdg.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/pgdg.gpg] http://apt.postgresql.org/pub/repos/apt noble-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y $MYSQL_CLIENT \
    && apt-get install -y postgresql-client-$POSTGRES_VERSION \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# --- Capa 2: PHP y composer -----------------------------------------------
#
# El ARG se reference dentro del comando a propósito: al cambiarlo en cada
# despliegue (--build-arg PHP_REFRESH=$(date +%s)) Docker invalida SOLO esta
# capa y vuelve a instalar PHP con la última versión publicada. Así los
# parches de seguridad entran sin reconstruir la imagen completa.
#
# Composer se instala aquí porque su instalador es un script de PHP: necesita
# que PHP ya esté presente.
ARG PHP_REFRESH=1
RUN echo "PHP_REFRESH=${PHP_REFRESH}" \
    && apt-get update \
    && apt-get install -y \
        libgd3 \
        php8.5-cli \
        php8.5-dev \
        php8.5-pgsql \
        php8.5-sqlite3 \
        php8.5-gd \
        php8.5-curl \
        php8.5-mongodb \
        php8.5-imap \
        php8.5-mysql \
        php8.5-mbstring \
        php8.5-xml \
        php8.5-zip \
        php8.5-bcmath \
        php8.5-soap \
        php8.5-intl \
        php8.5-readline \
        php8.5-ldap \
        php8.5-msgpack \
        php8.5-igbinary \
        php8.5-redis \
        php8.5-swoole \
        php8.5-memcached \
        php8.5-pcov \
        php8.5-imagick \
        php8.5-xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && php -v \
    && php -r 'if (version_compare(PHP_VERSION, "8.5.12", "<")) { fwrite(STDERR, "PHP demasiado antiguo: " . PHP_VERSION . PHP_EOL); exit(1); }'

RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.5

RUN userdel -r ubuntu
RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail
RUN git config --global --add safe.directory /var/www/html

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.5/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container

EXPOSE 80/tcp

ENTRYPOINT ["start-container"]
