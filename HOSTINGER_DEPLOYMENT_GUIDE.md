# 🚀 Hostinger Deployment Guide - Store 2 POS

Yeh guide step-by-step batati hai ke Hostinger par **Store 2 POS** ko asani se kaise host karna hai.

---

## 🏬 Store 2 POS Deployment Steps

### Step 1: Hostinger par Database Create Karein
1. Hostinger **hPanel** mein login karein.
2. **Databases** -> **MySQL Databases** par jayein.
3. Naya database banayein:
   - **Database Name**: e.g., `u123456789_store_2_pos`
   - **Username**: e.g., `u123456789_admin`
   - **Password**: Strong password rakhein aur note kar lein (e.g., `Store2Pass#2026`).
4. **Create** button par click karein.

### Step 2: Database Data Import Karein
1. hPanel mein database ke samne **Enter phpMyAdmin** button par click karein.
2. Left panel se apne database (`u123456789_store_2_pos`) par click karein.
3. Top bar mein **Import** par click karein.
4. **Choose File** par click karke project folder se yeh file select karein:
   📁 `database/schema.sql` (ya aapka backup sql)
5. Page ke bottom par **Go** / **Import** par click karein.

### Step 3: Files Upload Karein
1. Hostinger **File Manager** (ya FileZilla FTP) open karein.
2. `public_html` directory mein jayein.
3. Project ki tamam files aur folders upload karein.

### Step 4: Database Credentials Set Karein (.env)
1. Project root folder mein `.env.example` file ka naam rename karke `.env` kar dein (ya nayi `.env` file banayein).
2. Is file mein apne Hostinger database credentials daal dein:
   ```env
   STORE_NAME="One Dollar Shop"
   STORE_TAGLINE="Wholesale & Retail Discount Center"
   STORE_CURRENCY="Rs."
   STORE_ADDRESS="McConaghey Road, Quetta"
   STORE_PHONE="0307-2681893"
   STORE_LOGO="one_dollar_shop_logo.png"

   DB_HOST="localhost"
   DB_USER="u123456789_admin"
   DB_PASS="Store2Pass#2026"
   DB_NAME="u123456789_store_2_pos"
   ```
3. File save kar lein!

---

## 🔑 Default Login Credentials

| Role | Username | Default Password | Access Level |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | Dashboard, Reports, Inventory, Audit Logs, Settings |
| **Cashier** | `cashier` | `cashier123` | High-Speed POS, Sales History, Khata, Refunds |

> ⚠️ **Login karte hi**: Top-right user menu se **Change Password** par click karke apna naya secure password rakh lein.
