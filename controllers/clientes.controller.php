<?php

class ControladorClientes{

    public function index() {
        try {
            $clientes = ModeloClientes::index("clientes");
            
            ResponseHelper::success([
                "total" => count($clientes),
                "detalle" => $clientes
            ]);
            
        } catch (Exception $e) {
            ResponseHelper::serverError("Error al obtener clientes");
        }
    }

    public function create($datos) {
        try {
            // Validar datos requeridos
            if (!isset($datos["nombre"]) || !isset($datos["apellido"]) || !isset($datos["email"])) {
                ResponseHelper::badRequest("Campos requeridos: nombre, apellido, email");
            }

            // Validar y sanitizar datos
            $nombre = ValidationHelper::validateName($datos["nombre"], "nombre");
            $apellido = ValidationHelper::validateName($datos["apellido"], "apellido");
            $email = ValidationHelper::validateEmail($datos["email"]);

            // Validar el email repetido
            $clientes = ModeloClientes::index("clientes");

            foreach ($clientes as $key => $value) {
                if ($value["email"] == $email) {
                    ResponseHelper::error("El email ya está registrado", 409);
                }
            }

            // Generar credenciales del cliente
            $id_cliente = str_replace("$", "c", crypt($nombre . $apellido . $email, '$2a$07$afartwetsdAD52356FEDGsfhsd$'));
            $llave_secreta = str_replace("$", "a", crypt($email . $apellido . $nombre, '$2a$07$afartwetsdAD52356FEDGsfhsd$'));

            $datosCliente = array(
                "nombre" => $nombre,
                "apellido" => $apellido,
                "email" => $email,
                "id_cliente" => $id_cliente,
                "llave_secreta" => $llave_secreta,
                "created_at" => date('Y-m-d h:i:s'),
                "updated_at" => date('Y-m-d h:i:s')
            );

            $create = ModeloClientes::create("clientes", $datosCliente);

            if ($create == "ok") {
                ResponseHelper::success([
                    "detalle" => "Se generaron sus credenciales",
                    "id_cliente" => $id_cliente,
                    "llave_secreta" => $llave_secreta
                ], "Cliente creado exitosamente", 201);
            } else {
                ResponseHelper::serverError("Error al crear el cliente");
            }

        } catch (Exception $e) {
            ResponseHelper::badRequest($e->getMessage());
        }
    }
}

?>