<!DOCTYPE html>
<html lang="es" data-theme="wireframe">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENA Control de Asistencia</title>

    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>

    
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.min.css" rel="stylesheet">

    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    
    <link rel="stylesheet" href="../public/css/login.css">
    <link rel="stylesheet" href="../public/css/toast.css">
</head>
<body class="fondo-login">

    
    <div class="shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    
    <div class="min-h-screen flex items-center justify-center px-4 py-8 relative z-10">
        <div class="w-full max-w-md">

            <!-- Logo y titulo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white shadow-lg mb-4 logo-sena">
                    <svg class="w-12 h-12" viewBox="0 0 100 100" fill="none">
                        <rect x="10" y="20" width="80" height="60" rx="8" fill="#0d9488"/>
                        <rect x="20" y="30" width="25" height="20" rx="4" fill="#fff"/>
                        <rect x="55" y="30" width="25" height="20" rx="4" fill="#fff"/>
                        <rect x="20" y="58" width="60" height="12" rx="4" fill="#fff"/>
                        <circle cx="70" cy="40" r="5" fill="#fbbf24"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">SENA Control</h1>
                <p class="text-teal-200 font-light">Sistema de Control de Asistencia</p>
            </div>

            
            <div class="card-glass rounded-3xl shadow-2xl p-8">

                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-slate-800">Bienvenido</h2>
                    <p class="text-slate-500 text-sm mt-1">Ingresa tus credenciales para acceder</p>
                </div>

                
                <form id="loginForm" class="space-y-5">

                    
                    <div class="input-group">
                        <i data-lucide="user" class="icon-input w-5 h-5"></i>
                        <input
                            type="text"
                            name="documento"
                            id="documento"
                            placeholder="Numero de documento"
                            class="input input-bordered w-full h-12 rounded-xl input-focus transition-all duration-200"
                            required
                            autocomplete="username"
                        >
                    </div>

                    
                    <div class="input-group">
                        <i data-lucide="lock" class="icon-input w-5 h-5"></i>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Contrasena"
                            class="input input-bordered w-full h-12 rounded-xl input-focus transition-all duration-200 pr-10"
                            required
                            autocomplete="current-password"
                        >
                        <span class="password-toggle" onclick="togglePassword()">
                            <i data-lucide="eye" id="eyeIcon" class="w-5 h-5"></i>
                        </span>
                    </div>

                    
                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="checkbox checkbox-sm checkbox-success" name="recordar">
                            <span class="text-slate-600">Recordarme</span>
                        </label>
                    </div>

                    
                    <button type="submit" class="btn btn-login w-full h-12 rounded-xl text-white font-semibold text-base border-none">
                        <i data-lucide="log-in" class="w-5 h-5 mr-2"></i>
                        Iniciar Sesion
                    </button>

                </form>

                
                <div class="divider my-6 text-slate-400 text-xs">ADSO</div>
                <div class="text-center">
                    <p class="text-slate-500 text-xs">Analisis y Desarrollo de Software</p>
                    <p class="text-slate-400 text-xs mt-1">Juan Camilo Vanegas Gonzalez</p>
                </div>

            </div>

            
            <div class="text-center mt-6">
                <p class="text-teal-200 text-xs">&copy; 2026 - Todos los derechos reservados</p>
            </div>

        </div>
    </div>

    
    <script src="https://unpkg.com/lucide@latest"></script>

    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    
    <script src="../public/js/toast.js"></script>
    <script src="../public/js/login.js"></script>

</body>
</html>
