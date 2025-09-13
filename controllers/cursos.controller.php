<?php 

class ControladorCursos{
    
    /**
     * Validate client credentials using HTTP Basic Auth
     * @return array|null Client data if valid, null if invalid
     */
    private function validateClientCredentials() {
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
            return null;
        }
        
        $clientes = ModeloClientes::index("clientes");
        $authHeader = base64_encode($_SERVER['PHP_AUTH_USER'].":".$_SERVER['PHP_AUTH_PW']);
        
        foreach($clientes as $cliente) {
            $clientAuth = base64_encode($cliente["id_cliente"].":".$cliente["llave_secreta"]);
            if ($authHeader === $clientAuth) {
                return $cliente;
            }
        }
        
        return null;
    }

    public function index($pagina) {
        try {
            $cliente = $this->validateClientCredentials();
            if (!$cliente) {
                ResponseHelper::unauthorized("Credenciales de cliente inválidas");
            }

            if ($pagina != null) {
                if (!is_numeric($pagina) || $pagina < 1) {
                    ResponseHelper::badRequest("Número de página inválido");
                }
                
                $cantidad = 10;
                $desde = ($pagina-1)*$cantidad;
                $cursos = ModeloCursos::index("cursos", "clientes", $cantidad, $desde);
            } else {
                $cursos = ModeloCursos::index("cursos", "clientes", null, null);
            }  

            ResponseHelper::success([
                "total_registros" => count($cursos),
                "detalle" => $cursos
            ]);

        } catch (Exception $e) {
            ResponseHelper::serverError("Error al obtener cursos");
        }
    }

    public function create($datos) {
        try {
            $cliente = $this->validateClientCredentials();
            if (!$cliente) {
                ResponseHelper::unauthorized("Credenciales de cliente inválidas");
            }

            // Validar campos requeridos
            $requiredFields = ["titulo", "descripcion", "instructor", "precio"];
            foreach ($requiredFields as $field) {
                if (!isset($datos[$field])) {
                    ResponseHelper::badRequest("Campo requerido: {$field}");
                }
            }

            // Validar y sanitizar datos
            $titulo = ValidationHelper::validateText($datos["titulo"], "titulo", 3, 100);
            $descripcion = ValidationHelper::validateText($datos["descripcion"], "descripcion", 10, 500);
            $instructor = ValidationHelper::validateText($datos["instructor"], "instructor", 3, 100);
            $precio = ValidationHelper::validatePrice($datos["precio"]);
            $imagen = isset($datos["imagen"]) ? ValidationHelper::validateText($datos["imagen"], "imagen", 0, 255) : null;

            // Validar que el título no esté repetido
            $cursos = ModeloCursos::index("cursos", "clientes", null, null);
            foreach($cursos as $curso) {
                if($curso->titulo == $titulo){
                    ResponseHelper::error("El título ya existe en la base de datos", 409);
                }
            }

            // Preparar datos para el modelo
            $datosModelo = array(
                "titulo" => $titulo,
                "descripcion" => $descripcion,
                "instructor" => $instructor,
                "imagen" => $imagen,
                "precio" => $precio,
                "id_creador" => $cliente["id"],
                "created_at" => date('Y-m-d h:i:s'),
                "updated_at" => date('Y-m-d h:i:s')
            );

            $create = ModeloCursos::create("cursos", $datosModelo);

            if($create == "ok"){
                ResponseHelper::success(
                    ["detalle" => "Registro exitoso, su curso ha sido guardado"],
                    "Curso creado exitosamente",
                    201
                );
            } else {
                ResponseHelper::serverError("Error al crear el curso");
            }

        } catch (Exception $e) {
            ResponseHelper::badRequest($e->getMessage());
        }
    }

    public function show($id) {
        try {
            $cliente = $this->validateClientCredentials();
            if (!$cliente) {
                ResponseHelper::unauthorized("Credenciales de cliente inválidas");
            }

            if (!is_numeric($id) || $id < 1) {
                ResponseHelper::badRequest("ID de curso inválido");
            }

            $curso = ModeloCursos::show("cursos", "clientes", $id);

            if(!empty($curso)){
                ResponseHelper::success($curso);
            } else {
                ResponseHelper::success(
                    ["detalle" => "No hay ningún curso registrado"],
                    null,
                    200
                );
            }

        } catch (Exception $e) {
            ResponseHelper::serverError("Error al obtener el curso");
        }
    }

    public function update($id, $datos) {
        try {
            $cliente = $this->validateClientCredentials();
            if (!$cliente) {
                ResponseHelper::unauthorized("Credenciales de cliente inválidas");
            }

            if (!is_numeric($id) || $id < 1) {
                ResponseHelper::badRequest("ID de curso inválido");
            }

            // Validar y sanitizar datos
            $datosValidados = [];
            if (isset($datos["titulo"])) {
                $datosValidados["titulo"] = ValidationHelper::validateText($datos["titulo"], "titulo", 3, 100);
            }
            if (isset($datos["descripcion"])) {
                $datosValidados["descripcion"] = ValidationHelper::validateText($datos["descripcion"], "descripcion", 10, 500);
            }
            if (isset($datos["instructor"])) {
                $datosValidados["instructor"] = ValidationHelper::validateText($datos["instructor"], "instructor", 3, 100);
            }
            if (isset($datos["precio"])) {
                $datosValidados["precio"] = ValidationHelper::validatePrice($datos["precio"]);
            }
            if (isset($datos["imagen"])) {
                $datosValidados["imagen"] = ValidationHelper::validateText($datos["imagen"], "imagen", 0, 255);
            }

            // Verificar que el cliente es el creador del curso
            $curso = ModeloCursos::show("cursos", "clientes", $id);
            $authorized = false;
            
            foreach($curso as $valueCurso) {
                if($valueCurso->id_creador == $cliente["id"]){
                    $authorized = true;
                    break;
                }
            }

            if (!$authorized) {
                ResponseHelper::forbidden("No está autorizado para modificar este curso");
            }

            // Preparar datos para actualización
            $datosValidados["id"] = $id;
            $datosValidados["updated_at"] = date('Y-m-d h:i:s');

            $update = ModeloCursos::update("cursos", $datosValidados);

            if($update == "ok"){
                ResponseHelper::success(["detalle" => "Registro exitoso, su curso ha sido actualizado"]);
            } else {
                ResponseHelper::serverError("Error al actualizar el curso");
            }

        } catch (Exception $e) {
            ResponseHelper::badRequest($e->getMessage());
        }
    }

    public function delete($id) {
        try {
            $cliente = $this->validateClientCredentials();
            if (!$cliente) {
                ResponseHelper::unauthorized("Credenciales de cliente inválidas");
            }

            if (!is_numeric($id) || $id < 1) {
                ResponseHelper::badRequest("ID de curso inválido");
            }

            // Verificar que el cliente es el creador del curso
            $curso = ModeloCursos::show("cursos", "clientes", $id);
            $authorized = false;
            
            foreach($curso as $valueCurso) {
                if($valueCurso->id_creador == $cliente["id"]){
                    $authorized = true;
                    break;
                }
            }

            if (!$authorized) {
                ResponseHelper::forbidden("No está autorizado para eliminar este curso");
            }

            $delete = ModeloCursos::delete("cursos", $id); 
            
            if($delete == "ok"){
                ResponseHelper::success(["detalle" => "Se ha borrado el curso correctamente"]);
            } else {
                ResponseHelper::serverError("Error al eliminar el curso");
            }

        } catch (Exception $e) {
            ResponseHelper::badRequest($e->getMessage());
        }
    }
}

?>