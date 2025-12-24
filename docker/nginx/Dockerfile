FROM nginx:alpine

ARG ENVIRONMENT=prod

RUN apk update && apk add --no-cache \
    certbot \
    certbot-nginx \
    bash \
    openssl

COPY nginx.conf /etc/nginx/nginx.conf

COPY conf.d/locations.conf /etc/nginx/conf.d/
COPY conf.d/${ENVIRONMENT}.conf /etc/nginx/conf.d/default.conf
COPY docker-entrypoint.sh /

RUN chmod +x /docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/docker-entrypoint.sh"]
