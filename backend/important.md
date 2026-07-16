# Deployment & Operations Reference

## Architecture

```
Internet (HTTPS) → Hostinger host nginx (Let's Encrypt)
                        ↓ plain HTTP
      ┌─────────────────────────────────┐
      │  Docker containers              │
      │  :8200 → Doctor Next.js         │
      │  :8201 → Patient Next.js        │
      │  :8210 → Backend nginx (HTTP)   │
      │           └─ app:9000 (PHP-FPM) │
      │  :8281 → phpMyAdmin             │
      └─────────────────────────────────┘
```

---

## First-Time VPS Setup (Hostinger)

```bash
# 1. Clone the repo
git clone <repo-url> ~/dr-sushil-telehealth
cd ~/dr-sushil-telehealth

# 2. Create root .env for docker-compose build args
cp .env.example .env
# (values are already correct for production)

# 3. Create backend .env (copy your local one or fill manually)
# Must have APP_URL=https://dr-sushil-clinic.deploymeta.com
nano backend/.env

# 4. Install host nginx + Certbot (if not already done)
apt install -y nginx certbot python3-certbot-nginx

# 5. Deploy the nginx proxy config
cp nginx-proxy.conf /etc/nginx/sites-available/dr-sushil-clinic
ln -sf /etc/nginx/sites-available/dr-sushil-clinic \
       /etc/nginx/sites-enabled/dr-sushil-clinic
nginx -t && systemctl reload nginx

# 6. Obtain SSL certificate
certbot --nginx -d dr-sushil-clinic.deploymeta.com
# (certbot auto-renews; check: certbot renew --dry-run)

# 7. Build and start all containers
docker compose -f docker-compose.prod.yml up -d --build

# 8. Run post-start backend setup
docker exec -it dr-sushil-backend-app php artisan migrate --force
docker exec -it dr-sushil-backend-app php artisan storage:link
docker exec -it dr-sushil-backend-app php artisan config:cache
docker exec -it dr-sushil-backend-app php artisan route:cache
```

---

## Deploying Updates (After git push from local)

```bash
cd ~/dr-sushil-telehealth

# Pull latest code
git pull origin main

# Rebuild & restart all services
docker compose -f docker-compose.prod.yml up -d --build

# Clear Laravel caches after code update
docker exec -it dr-sushil-backend-app php artisan config:clear
docker exec -it dr-sushil-backend-app php artisan cache:clear
docker exec -it dr-sushil-backend-app php artisan route:clear
docker exec -it dr-sushil-backend-app php artisan view:clear
docker exec -it dr-sushil-backend-app php artisan migrate --force
```

---

## Rebuild Individual Services

```bash
# Patient frontend
docker compose -f docker-compose.prod.yml up -d --build patient

# Doctor frontend (build on server)
docker compose -f docker-compose.prod.yml up -d --build doctor

# Backend PHP-FPM + Nginx
docker compose -f docker-compose.prod.yml up -d --build app web
```

---

## Build Doctor Image Locally (Mac → VPS)

Build on local Mac for Linux AMD64 (faster than building on VPS):

```bash
# 1. Build image locally
docker buildx build --platform linux/amd64 \
  -t dr-sushil-doctor:latest \
  --build-arg NEXT_PUBLIC_API_BASE_URL=https://dr-sushil-clinic.deploymeta.com/api/v2 \
  --build-arg NEXT_PUBLIC_API_URL=https://dr-sushil-clinic.deploymeta.com/api/v2 \
  ./frontend/doctor --load

# 2. Export image
docker save dr-sushil-doctor:latest -o dr-sushil-doctor.tar

# 3. Copy to VPS (replace with your VPS IP)
scp dr-sushil-doctor.tar root@<VPS_IP>:~/dr-sushil-telehealth/

# 4. On VPS: load and start
docker load -i ~/dr-sushil-telehealth/dr-sushil-doctor.tar
cd ~/dr-sushil-telehealth
docker compose -f docker-compose.prod.yml up -d --no-build doctor
```

---

## Backend Vite Assets (Admin Panel)

The Vite build runs automatically in the Docker app container on first start
(via entrypoint.sh) if `public/build/manifest.json` is missing.

To force a manual rebuild inside the container:
```bash
docker exec -it dr-sushil-backend-app sh -c "cd /var/www/html && npm ci && npm run build"
```

---

## Useful Backend Commands

```bash
# Artisan shortcuts
docker exec -it dr-sushil-backend-app php artisan storage:link
docker exec -it dr-sushil-backend-app php artisan config:clear
docker exec -it dr-sushil-backend-app php artisan cache:clear
docker exec -it dr-sushil-backend-app php artisan migrate
docker exec -it dr-sushil-backend-app php artisan migrate:fresh --seed
docker exec -it dr-sushil-backend-app php artisan queue:work --daemon

# Fix storage permissions
docker exec -it dr-sushil-backend-app sh -lc \
  'chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
  && find /var/www/html/storage /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \; \
  && find /var/www/html/storage /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \; \
  && php artisan storage:link \
  && php artisan config:clear \
  && php artisan cache:clear'
```

---

## Restart Everything

```bash
docker compose -f docker-compose.prod.yml down && docker compose -f docker-compose.prod.yml up -d
```

---

## Logs

```bash
docker logs dr-sushil-backend-web   # nginx access/error logs
docker logs dr-sushil-backend-app   # PHP-FPM logs
docker logs dr-sushil-doctor        # Doctor Next.js logs
docker logs dr-sushil-patient       # Patient Next.js logs
```