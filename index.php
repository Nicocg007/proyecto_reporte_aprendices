<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-dark">

    <div class="container mb-3">
        <div class="row justify-content-center mt-5">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">ServiPlus</h3>

                        <form action="Controllers/login.php" method="POST">

                            <div class="mb-3">
                                <label class="form-label">Documento</label>
                                <input type="text" name="documento" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="contraseña" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Ingresar</button>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>