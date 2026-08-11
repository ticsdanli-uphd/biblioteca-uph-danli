<?php
session_start();
include 'config/db.php';

// Verificar si existe al menos un usuario administrador
$adminExists = false;
$sqlAdminCheck = "SELECT id FROM usuarios WHERE role = 'admin'";
$resultAdminCheck = $conn->query($sqlAdminCheck);
if ($resultAdminCheck && $resultAdminCheck->num_rows > 0) {
    $adminExists = true;
}

$error = "";

// Procesar el formulario solo si ya existe un administrador
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $adminExists) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    // La autenticación NO depende de las tablas alumnos o docentes.
    // Esto evita que el inicio de sesión falle si todavía no se ha ejecutado
    // la migración de docentes en una base de datos existente.
    $stmt = $conn->prepare("
        SELECT id, username, nombre, password, role, sede_id
        FROM usuarios
        WHERE username = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['sede_id'] = (int)$user['sede_id'];
            $_SESSION['sede_seleccionada'] = (int)$user['sede_id'];

            // El nombre se guarda directamente en usuarios al crear la cuenta.
            $_SESSION['nombre_completo'] = !empty($user['nombre'])
                ? $user['nombre']
                : $user['username'];

            header("Location: /biblioteca/dashboard.php");
            exit();
        } else {
            $error = "Credenciales incorrectas.";
        }
    } else {
        $error = "Usuario no encontrado.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">  
  <title>Iniciar Sesión - Biblioteca UPH</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Fonts: Inter -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
  <!-- Animate.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
      --secondary-gradient: linear-gradient(135deg, #ffd700 0%, #ffb347 100%);
      --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
      --accent-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
      --glass-bg: rgba(255, 255, 255, 0.15);
      --glass-border: rgba(255, 255, 255, 0.2);
      --shadow-light: 0 8px 32px 0 rgba(79, 172, 254, 0.37);
      --shadow-heavy: 0 20px 40px rgba(0, 0, 0, 0.15);
      --text-primary: #1a1a1a;
      --text-secondary: rgba(26, 26, 26, 0.8);
      --text-muted: rgba(26, 26, 26, 0.6);
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 50%, #ffd700 100%);
      background-size: 400% 400%;
      animation: gradientShift 20s ease infinite;
      min-height: 100vh;
      overflow-x: hidden;
      position: relative;
    }
    
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at 20% 80%, rgba(79, 172, 254, 0.3) 0%, transparent 50%),
                  radial-gradient(circle at 80% 20%, rgba(255, 215, 0, 0.3) 0%, transparent 50%);
                  radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
      pointer-events: none;
      z-index: 1;
    }
    
    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    
    .floating-shapes {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 1;
      overflow: hidden;
    }
    
    .shape {
      position: absolute;
      border-radius: 50%;
      animation: float 8s ease-in-out infinite;
      opacity: 0.6;
    }
    
    .shape:nth-child(1) {
      width: 100px;
      height: 100px;
      top: 15%;
      left: 8%;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(79, 172, 254, 0.3));
      animation-delay: 0s;
      animation-duration: 12s;
    }
    
    .shape:nth-child(2) {
      width: 150px;
      height: 150px;
      top: 55%;
      right: 8%;
      background: linear-gradient(135deg, rgba(255, 215, 0, 0.3), rgba(255, 179, 71, 0.2));
      animation-delay: 3s;
      animation-duration: 15s;
    }
    
    .shape:nth-child(3) {
      width: 80px;
      height: 80px;
      bottom: 25%;
      left: 15%;
      background: linear-gradient(135deg, rgba(79, 172, 254, 0.3), rgba(0, 242, 254, 0.2));
      animation-delay: 6s;
      animation-duration: 10s;
    }
    
    .shape:nth-child(4) {
      width: 60px;
      height: 60px;
      top: 30%;
      right: 25%;
      background: linear-gradient(135deg, rgba(67, 233, 123, 0.3), rgba(56, 249, 215, 0.2));
      animation-delay: 9s;
      animation-duration: 14s;
    }
    
    .shape:nth-child(5) {
      width: 120px;
      height: 120px;
      bottom: 10%;
      right: 30%;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(79, 172, 254, 0.3));
      animation-delay: 12s;
      animation-duration: 18s;
    }
    
    @keyframes float {
      0%, 100% { 
        transform: translateY(0px) translateX(0px) rotate(0deg) scale(1);
        opacity: 0.6;
      }
      25% {
        transform: translateY(-30px) translateX(20px) rotate(90deg) scale(1.1);
        opacity: 0.8;
      }
      50% { 
        transform: translateY(-60px) translateX(0px) rotate(180deg) scale(0.9);
        opacity: 0.4;
      }
      75% {
        transform: translateY(-30px) translateX(-20px) rotate(270deg) scale(1.05);
        opacity: 0.7;
      }
    }
    
    .login-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      z-index: 2;
    }
    
    .login-card {
      max-width: 450px;
      width: 100%;
      background: var(--glass-bg);
      backdrop-filter: blur(25px);
      -webkit-backdrop-filter: blur(25px);
      border: 2px solid var(--glass-border);
      border-radius: 24px;
      box-shadow: var(--shadow-light), 0 0 60px rgba(79, 172, 254, 0.2);
      padding: 45px 40px;
      position: relative;
      overflow: hidden;
      animation: slideInUp 1s ease-out;
      transform-style: preserve-3d;
    }
    
    .login-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--primary-gradient);
      border-radius: 24px 24px 0 0;
      animation: shimmer 3s ease-in-out infinite;
    }
    
    .login-card::after {
      content: '';
      position: absolute;
      top: -2px;
      left: -2px;
      right: -2px;
      bottom: -2px;
      background: linear-gradient(45deg, 
         rgba(79, 172, 254, 0.3) 0%, 
         rgba(255, 215, 0, 0.3) 25%, 
         rgba(79, 172, 254, 0.3) 50%, 
         rgba(67, 233, 123, 0.3) 75%, 
         rgba(79, 172, 254, 0.3) 100%);
      border-radius: 26px;
      z-index: -1;
      animation: borderGlow 4s ease-in-out infinite;
      opacity: 0.6;
    }
    
    @keyframes shimmer {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }
    
    @keyframes borderGlow {
      0%, 100% { opacity: 0.6; transform: scale(1); }
      50% { opacity: 0.8; transform: scale(1.02); }
    }
    
    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .logo-container {
      text-align: center;
      margin-bottom: 35px;
      position: relative;
    }
    
    .logo-container::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 180px;
      height: 130px;
      background: radial-gradient(circle, rgba(79, 172, 254, 0.3) 0%, transparent 70%);
      border-radius: 50%;
      animation: logoGlow 3s ease-in-out infinite;
      z-index: -1;
    }
    
    .logo {
      width: 160px;
      height: 110px;
      border-radius: 20px;
      object-fit: contain;
      border: 3px solid rgba(255, 255, 255, 0.4);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2), 0 5px 15px rgba(79, 172, 254, 0.3);
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      animation: logoFloat 4s ease-in-out infinite;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
    }
    
    @keyframes logoGlow {
      0%, 100% { opacity: 0.6; transform: translate(-50%, -50%) scale(1); }
      50% { opacity: 1; transform: translate(-50%, -50%) scale(1.1); }
    }
    
    @keyframes logoFloat {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-10px) rotate(1deg); }
    }
    
    .logo:hover {
      transform: scale(1.08) translateY(-5px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 10px 20px rgba(79, 172, 254, 0.4);
      border-color: rgba(255, 255, 255, 0.6);
    }
    
    .login-title {
      font-weight: 800;
      font-size: 2rem;
      margin-bottom: 12px;
      text-align: center;
      color: #1a1a1a;
      text-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
      background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      animation: titleGlow 3s ease-in-out infinite;
      letter-spacing: -0.5px;
    }
    
    @keyframes titleGlow {
      0%, 100% { text-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); }
      50% { text-shadow: 0 4px 25px rgba(79, 172, 254, 0.5), 0 0 30px rgba(255, 255, 255, 0.3); }
    }
    
    .login-subtitle {
      text-align: center;
      color: rgba(26, 26, 26, 0.7);
      font-size: 1rem;
      margin-bottom: 35px;
      font-weight: 500;
      letter-spacing: 0.5px;
      animation: subtitleFade 2s ease-in-out;
    }
    
    @keyframes subtitleFade {
      0% { opacity: 0; transform: translateY(10px); }
      100% { opacity: 1; transform: translateY(0); }
    }
    
    .form-floating {
      position: relative;
      margin-bottom: 20px;
    }
    
    .form-control {
      background: rgba(255, 255, 255, 0.9);
      border: 2px solid rgba(255, 255, 255, 0.15);
      border-radius: 16px;
      color: #1a1a1a;
      font-size: 1rem;
      padding: 18px 20px;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      backdrop-filter: blur(15px);
      position: relative;
      font-weight: 500;
    }
    
    .form-control:focus {
      background: rgba(255, 255, 255, 0.95);
      border-color: rgba(79, 172, 254, 0.6);
      box-shadow: 0 0 0 4px rgba(79, 172, 254, 0.15), 0 8px 25px rgba(79, 172, 254, 0.2);
      color: var(--text-primary);
      transform: translateY(-2px);
    }
    
    .form-control::placeholder {
      color: rgba(26, 26, 26, 0.5);
      font-weight: 300;
    }
    
    .form-floating label {
      color: var(--text-secondary);
      font-weight: 600;
      font-size: 0.9rem;
      transition: all 0.3s ease;
    }
    
    .form-floating > .form-control:focus ~ label,
    .form-floating > .form-control:not(:placeholder-shown) ~ label {
      color: rgba(79, 172, 254, 1);
      transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
    }
    
    .input-group {
      position: relative;
    }
    
    .input-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(26, 26, 26, 0.6);
      z-index: 10;
      font-size: 1.1rem;
    }
    
    .form-control.with-icon {
      padding-left: 45px;
    }
    
    .btn-login {
      background: var(--primary-gradient);
      border: none;
      border-radius: 16px;
      color: var(--text-primary);
      font-weight: 700;
      font-size: 1.1rem;
      padding: 18px 24px;
      width: 100%;
      margin-top: 15px;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      position: relative;
      overflow: hidden;
      box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
      letter-spacing: 0.5px;
      text-transform: uppercase;
      font-size: 0.95rem;
    }
    
    .btn-login::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
      transition: left 0.6s ease;
    }
    
    .btn-login::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      transform: translate(-50%, -50%);
      transition: width 0.6s ease, height 0.6s ease;
    }
    
    .btn-login:hover::before {
      left: 100%;
    }
    
    .btn-login:hover::after {
      width: 300px;
      height: 300px;
    }
    
    .btn-login:hover {
      transform: translateY(-4px) scale(1.02);
      box-shadow: 0 15px 35px rgba(79, 172, 254, 0.4), 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .btn-login:active {
      transform: translateY(-2px) scale(1.01);
      transition: all 0.1s ease;
    }
    
    .btn-admin {
      background: var(--success-gradient);
      border: none;
      border-radius: 16px;
      color: var(--text-primary);
      font-weight: 700;
      padding: 18px 24px;
      width: 100%;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      position: relative;
      overflow: hidden;
      box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
      letter-spacing: 0.5px;
      text-transform: uppercase;
      font-size: 0.95rem;
    }
    
    .btn-admin:hover {
      transform: translateY(-4px) scale(1.02);
      box-shadow: 0 15px 35px rgba(79, 172, 254, 0.4), 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .alert {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      color: white;
      backdrop-filter: blur(10px);
      margin-bottom: 20px;
    }
    
    .alert-danger {
      background: rgba(239, 68, 68, 0.2);
      border-color: rgba(239, 68, 68, 0.3);
    }
    
    .alert-info {
      background: rgba(59, 130, 246, 0.2);
      border-color: rgba(59, 130, 246, 0.3);
    }
    
    .password-toggle {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.6);
      cursor: pointer;
      z-index: 10;
      font-size: 1.1rem;
      transition: color 0.3s ease;
    }
    
    .password-toggle:hover {
      color: rgba(255, 255, 255, 0.9);
    }
    
    @media (max-width: 480px) {
      .login-card {
        margin: 10px;
        padding: 30px 25px;
      }
      
      .login-title {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <!-- Floating Shapes Background -->
  <div class="floating-shapes">
    <div class="shape"></div>
    <div class="shape"></div>
    <div class="shape"></div>
    <div class="shape"></div>
    <div class="shape"></div>
  </div>
  
  <div class="login-container">
    <div class="login-card animate__animated animate__fadeInUp">
      <div class="logo-container">
        <img src="/biblioteca/imagenes/logouph.png" alt="Logo UPH" class="logo">
      </div>
      
      <h2 class="login-title">Biblioteca UPH</h2>
      <p class="login-subtitle">Sistema de Gestión Bibliotecaria</p>
      
      <?php if (!$adminExists): ?>
        <!-- Si no existe un administrador, se muestra un mensaje y un botón para crearlo -->
        <div class="alert alert-info text-center animate__animated animate__bounceIn" role="alert">
          <i class="fas fa-info-circle me-2"></i>
          No se encontró un usuario administrador en el sistema.
        </div>
        <div class="d-grid">
          <a href="setup_admin.php" class="btn btn-admin">
            <i class="fas fa-user-shield me-2"></i>
            Crear Usuario Administrador
          </a>
        </div>
      <?php else: ?>
        <?php if(isset($error)): ?>
          <div class="alert alert-danger text-center animate__animated animate__shakeX" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
          </div>
        <?php endif; ?>
        
        <!-- Formulario de login normal -->
        <form method="post" action="login.php" id="loginForm">
          <div class="input-group mb-3">
            <i class="fas fa-envelope input-icon"></i>
            <div class="form-floating w-100">
              <input type="text" name="username" id="username" class="form-control with-icon" placeholder="Email" required>
              <label for="username">Email o Usuario</label>
            </div>
          </div>
          
          <div class="input-group mb-4">
            <i class="fas fa-lock input-icon"></i>
            <div class="form-floating w-100">
              <input type="password" name="password" id="password" class="form-control with-icon" placeholder="Contraseña" required>
              <label for="password">Contraseña</label>
              <button type="button" class="password-toggle" onclick="togglePassword()">
                <i class="fas fa-eye" id="toggleIcon"></i>
              </button>
            </div>
          </div>
          
          <button type="submit" class="btn btn-login" id="loginBtn">
            <i class="fas fa-sign-in-alt me-2"></i>
            <span id="btnText">Iniciar Sesión</span>
            <div class="spinner-border spinner-border-sm d-none" id="loginSpinner" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle password visibility
function togglePassword() {
  const passwordInput = document.getElementById('password');
  const toggleIcon = document.getElementById('toggleIcon');
  
  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    toggleIcon.classList.remove('fa-eye');
    toggleIcon.classList.add('fa-eye-slash');
  } else {
    passwordInput.type = 'password';
    toggleIcon.classList.remove('fa-eye-slash');
    toggleIcon.classList.add('fa-eye');
  }
}

// Enhanced form submission with loading state
document.getElementById('loginForm')?.addEventListener('submit', function(e) {
  const loginBtn = document.getElementById('loginBtn');
  const btnText = document.getElementById('btnText');
  const spinner = document.getElementById('loginSpinner');
  
  // Show loading state
  btnText.classList.add('d-none');
  spinner.classList.remove('d-none');
  loginBtn.disabled = true;
  
  // Add a small delay for better UX
  setTimeout(() => {
    // Form will submit naturally
  }, 500);
});

// Input focus animations
document.querySelectorAll('.form-control').forEach(input => {
  input.addEventListener('focus', function() {
    this.parentElement.style.transform = 'scale(1.02)';
    this.parentElement.style.transition = 'transform 0.3s ease';
  });
  
  input.addEventListener('blur', function() {
    this.parentElement.style.transform = 'scale(1)';
  });
});

// Enhanced floating shapes animation
function createFloatingParticles() {
  const container = document.querySelector('.floating-shapes');
  const colors = [
    'linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(79, 172, 254, 0.2))',
    'linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 179, 71, 0.15))',
    'linear-gradient(135deg, rgba(79, 172, 254, 0.2), rgba(0, 242, 254, 0.15))',
    'linear-gradient(135deg, rgba(67, 233, 123, 0.2), rgba(56, 249, 215, 0.15))'
  ];
  
  for (let i = 0; i < 8; i++) {
    const particle = document.createElement('div');
    particle.className = 'shape';
    const size = Math.random() * 60 + 30;
    particle.style.width = size + 'px';
    particle.style.height = size + 'px';
    particle.style.left = Math.random() * 100 + '%';
    particle.style.top = Math.random() * 100 + '%';
    particle.style.background = colors[Math.floor(Math.random() * colors.length)];
    particle.style.animationDelay = Math.random() * 8 + 's';
    particle.style.animationDuration = (Math.random() * 6 + 8) + 's';
    particle.style.opacity = Math.random() * 0.4 + 0.3;
    container.appendChild(particle);
  }
}

// Mouse movement parallax effect
function initParallaxEffect() {
  document.addEventListener('mousemove', (e) => {
    const shapes = document.querySelectorAll('.shape');
    const x = e.clientX / window.innerWidth;
    const y = e.clientY / window.innerHeight;
    
    shapes.forEach((shape, index) => {
      const speed = (index + 1) * 0.5;
      const xPos = (x - 0.5) * speed;
      const yPos = (y - 0.5) * speed;
      shape.style.transform += ` translate(${xPos}px, ${yPos}px)`;
    });
  });
}

// Initialize enhanced effects
createFloatingParticles();
initParallaxEffect();

// Add keyboard navigation
document.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    const activeElement = document.activeElement;
    if (activeElement.id === 'username') {
      document.getElementById('password').focus();
      e.preventDefault();
    }
  }
});

// Add smooth scroll and page load animation
window.addEventListener('load', function() {
  document.body.style.opacity = '0';
  document.body.style.transition = 'opacity 0.5s ease';
  
  setTimeout(() => {
    document.body.style.opacity = '1';
  }, 100);
});

// Error message auto-hide
const errorAlert = document.querySelector('.alert-danger');
if (errorAlert) {
  setTimeout(() => {
    errorAlert.style.opacity = '0';
    errorAlert.style.transform = 'translateY(-10px)';
    errorAlert.style.transition = 'all 0.3s ease';
    setTimeout(() => {
      errorAlert.style.display = 'none';
    }, 300);
  }, 5000);
}
</script>
</body>
</html>
