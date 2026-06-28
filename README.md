# Brodev - Online Store

A modern E-Commerce application built using **Laravel (Backend)** + **Vue.js 3 (Frontend)** via **Inertia.js** and containerized with **Docker** for quick and seamless deployment.

The application features a clean, minimalist UI, supports a native **Light/Dark Mode**, and includes dynamic copyright rendering for the year **2026**.

---

## Key Features

1. **Multi-User & Multi-Role System**:
    * **Buyer**: Register, log in, browse the product catalog, search products (name-only matching), manage the shopping cart, checkout securely with stock validation, upload transfer payment proof, and track order histories.
    * **Seller**: Analytics dashboard, product management (CRUD) with photo upload, and manage incoming order statuses (`pending`, `processed`, `shipped`, `delivered`) along with shipping tracking numbers.
    * **Superadmin**: A unified account to simulate both Buyer and Seller roles in one single session using a dashboard toggle (Buyer Mode / Seller Mode) on the top navigation bar.
2. **Smooth Page Transitions & Top Loading Bar**: Integrates Inertia routing event hooks with Vue transitions (150ms-180ms) and a loading bar for a lightweight SPA-like user experience.
3. **Status History Timeline**: Comprehensive log of order status changes, showing who changed the status, when it occurred, and any related transaction notes.
4. **Bank Transfer Payment Verification**: Supports automatic order verification for bank transfers: orders start as `unpaid` (disabling seller updates), buyer uploads proof, status changes to `pending` and displays payment proof viewing link on the seller panel.
5. **Dockerized Environment**: Full services stack (PHP-FPM, Nginx, PostgreSQL, Node/Vite, Queue Worker) configured via Docker Compose.
6. **Zero-Downtime Deployment**: Configured with **Capistrano** for automated release management and atomic deployment switching.

---

## Tech Stack

* **Backend**: Laravel 11 (PHP 8.3)
* **Frontend**: Vue.js 3 (Inertia.js 2.0 + Vite)
* **Database**: PostgreSQL 15 (Docker default) / SQLite (Local testing)
* **Queue System**: Database queue connection with worker container (`brodev-queue`) for async processes (e.g., transactional emails).
* **Styling**: Custom Vanilla CSS (Outfit Font, responsive layouts, glassmorphism, micro-animations)
* **Deployment**: Capistrano 3

---

## Running Locally with Docker

Make sure you have **Docker** and **Docker Compose** installed on your machine.

### 1. Environment Setup
Copy the environment template:
```bash
cp .env.example .env
```
By default, the `.env` file is preset for the Docker database setup:
```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=brodev_store
DB_USERNAME=postgres
DB_PASSWORD=secret
```

### 2. Launch Docker Containers
Run the following command to download images, build, and run the containers in the background:
```bash
docker-compose up -d --build
```
This launches five services:
* `brodev-app`: PHP 8.3 FPM container.
* `brodev-queue`: Dedicated PHP background queue worker container.
* `brodev-web`: Nginx web server container (mapped to `http://localhost:8000`).
* `brodev-db`: PostgreSQL 15 database container (mapped to port `5432`).
* `brodev-node`: Node.js 20 container running the Vite dev server (mapped to port `5173`).

### 3. Initialize App and Run Migrations
Run these commands inside the app container to install dependencies, generate key, and seed dummy data:
```bash
# Install Composer dependencies
docker-compose exec app composer install

# Generate application key
docker-compose exec app php artisan key:generate

# Run fresh database migrations and seed default test data
docker-compose exec app php artisan migrate:fresh --seed
```

### 4. Access the Application
Open your browser and navigate to:
* **Online Store Application**: `http://localhost:8000`
* **Vite Hot Reload Server**: `http://localhost:5173`

---

## Default Test Credentials (Seeded)

The database includes the following default test accounts for quick demonstration:

1. **Superadmin**:
    * **Email**: `admin@brodev.com`
    * **Password**: `password`
    * *Access*: Can toggle between Buyer and Seller panels in a single login session.
2. **Seller**:
    * **Email**: `seller@brodev.com`
    * **Password**: `password`
    * *Access*: Manage products catalog and fulfill orders.
3. **Buyer**:
    * **Email**: `buyer@brodev.com`
    * **Password**: `password`
    * *Access*: Add items to cart, checkout, upload transfer proof, and track orders.

---

## Production Deployment with Capistrano

Capistrano manages zero-downtime atomic deployments.

### 1. Folder Structure on Target Server
Capistrano automatically creates and manages:
* `/var/www/brodev-store/releases/` (Old and new deployment builds)
* `/var/www/brodev-store/shared/` (Persistent `.env` and `storage` directories)
* `/var/www/brodev-store/current` (Dynamic symbolic link pointing to the latest release)

### 2. Triggering Deployments
1. Configure your target server details in `config/deploy/production.rb` or `config/deploy/staging.rb`.
2. Add your SSH public key to the target server's authorized keys list.
3. Run the deploy commands from your local machine:
    ```bash
    # Install Capistrano gems locally (requires Ruby)
    bundle install

    # Run deployment check
    bundle exec cap production deploy:check

    # Execute full zero-downtime deployment
    bundle exec cap production deploy
    ```

Capistrano automatically pulls from Git, installs composer packages, builds Vite assets, runs migrations, switches the `current` symlink, and reloads PHP-FPM for OPCache invalidation.

---

## Copyright
The copyright of this application is secured for the year 2026:
`© 2026 Brodev - Online Store. All rights reserved.`
