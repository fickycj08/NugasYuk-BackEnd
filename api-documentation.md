# API Documentation - NugasYuk

## Base URL
```
https://nugasyuk.com/backend_nugasyuk/api/
```

## Authentication (Auth)

### 1️⃣ Register User
**Endpoint:**
```
POST /auth/register.php
```
**Request Body:** (JSON)
```json
{
  "name": "admin",
  "email": "admin@example.com",
  "password": "secret123"
}
```
**Response:**
```json
{
  "status": "success",
  "message": "Registrasi berhasil!"
}
```

---

### 2️⃣ Login User
**Endpoint:**
```
POST /auth/login.php
```
**Headers:**
```
Content-Type: application/json
```
**Request Body:** (JSON)
```json
{
  "email": "admin@example.com",
  "password": "secret123"
}
```
**Response:**
```json
{
  "status": "success",
  "message": "Login berhasil!",
  "token": "session_token_here"
}
```

---

### 3️⃣ Forgot Password
**Endpoint:**
```
POST /auth/forgot-password.php
```
**Request Body:** (JSON)
```json
{
  "email": "ficky.julivano@gmail.com"
}
```
**Response:**
```json
{
  "status": "success",
  "message": "Kode OTP telah dikirim ke email."
}
```

---

### 4️⃣ Reset Password
**Endpoint:**
```
POST /auth/reset_password.php
```
**Request Body:** (JSON)
```json
{
  "email": "ficky.julivano@gmail.com",
  "otp": "748017",
  "new_password": "passwordbaru123"
}
```
**Response:**
```json
{
  "status": "success",
  "message": "Password berhasil diperbarui!"
}
```

---

## User Management

### 5️⃣ Update Profile
**Endpoint:**
```
PUT /users/update.php
```
**Headers:**
```
Authorization: Bearer {token}
```
**Request Body:** (JSON)
```json
{
  "name": "ficky",
  "email": "ficky.julivano@gmail.com",
  "password": "secret123",
  "profile_picture": "data:image/png;base64,<base64_image_data>"
}
```
**Response:**
```json
{
  "status": "success",
  "message": "Profil berhasil diperbarui!"
}
```

---

## Classes API

### 6️⃣ Create Class
**Endpoint:**
```
POST /classes/create.php
```
**Headers:**
```
Authorization: Bearer {token}
```
**Request Body:** (JSON)
```json
{
  "name": "Matematika asu",
  "description": "Kelas untuk belajar matematika dasar.",
  "category": "Sekolah"
}
```
**Response:**
```json
{
  "status": "success",
  "message": "Kelas berhasil dibuat!",
  "class_id": 2
}
```

---

### 7️⃣ Join Class
**Endpoint:**
```
POST /classes/join.php
```
**Headers:**
```
Authorization: Bearer {token}
```
**Request Body:** (JSON)
```json
{
  "code": "WAP54A"
}
```
**Response:**
```json
{
  "status": "success",
  "message": "Berhasil bergabung ke kelas!"
}
```

---

### 8️⃣ Get Class List
**Endpoint:**
```
GET /classes/index.php
```
**Headers:**
```
Authorization: Bearer {token}
```
**Response:**
```json
{
  "status": "success",
  "data": []
}
```

---

## Tasks API

### 9️⃣ Create Task
**Endpoint:**
```
POST /tasks/create.php
```
**Headers:**
```
Authorization: Bearer {token}
```
**Request Body:** (JSON)
```json
{
  "class_id": 29,
  "title": "Ta",
  "description": "Kerjakan soal trigonometri di halaman 45.",
  "deadline": "2024-05-30 23:59:00"
}
```
**Response:**
```json
{
  "status": "success",
  "message": "Tugas berhasil dibuat!"
}
```

---

### 🔟 Get Task List
**Endpoint:**
```
GET /tasks/index.php
```
**Headers:**
```
Authorization: Bearer {token}
```
**Query Params:**
```
class_id=29
```
**Response:**
```json
{
  "status": "success",
  "data": []
}
```

