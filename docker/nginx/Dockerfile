# Multi-stage build: compile the Angular SPA, then serve it (plus proxy /api to
# the Symfony backend) from a minimal Nginx image — see Jalon 4 n-tier design.

FROM node:22-alpine AS frontend-build
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ ./
RUN npx ng build --configuration production

FROM nginx:1.27-alpine
COPY docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=frontend-build /app/dist/frontend/browser /usr/share/nginx/html

EXPOSE 80
