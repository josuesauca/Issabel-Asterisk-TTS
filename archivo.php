#!/usr/bin/php -q
<?php
    require('/var/lib/asterisk/agi-bin/phpagi.php');
    $agi = new AGI();
    $agi-> answer();

    // Función para capturar las teclas y convertirlas en letras
    function obtenerClima($ciudad) {
        $apiKey = "6c3bd8127bd03202f4c2768018711000"; // Reemplaza con tu clave de API válida
        $url = "http://api.openweathermap.org/data/2.5/weather?q=" . urlencode($ciudad) . "&appid=" . $apiKey . "&lang=es&units=metric";
    
        $curl = curl_init();
    
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Accept: */*",
                "User-Agent: PHP-cURL"
            ],
        ]);
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
    
        curl_close($curl);
    
        if ($err) {
            return "Error en cURL: " . $err;
        } else {
            return json_decode($response, true); // Devuelve el JSON como un array asociativo
        }
    }

    function generarAudio($texto,$agi) {
	    echo " entra a la funcion";
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_PORT => "7860",
            CURLOPT_URL => "http://192.168.1.13:7860/convert",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'mensaje' => $texto,  // Se recibe como parámetro
                'modelo' => 'audio_juan'  // Modelo fijo
            ]),
            CURLOPT_HTTPHEADER => [
                "Accept: */*",
                "Content-Type: application/json",
                "User-Agent: Thunder Client (https://www.thunderclient.com)"
            ],
        ]);
	
        $response = curl_exec($curl);
        $err = curl_error($curl);

	    echo " hace algo la funcion";
        curl_close($curl);

        if ($err) {
            echo "cURL Error #: " . $err;
	    return;
        } else {

            $filepath = "/var/lib/asterisk/sounds/test.wav";
            file_put_contents($filepath, $response);  // Guarda el archivo WAV 

            $agi->stream_file('test'); 
            $agi->exec( "Playback","test"); 

            if (file_exists($filepath)) { 
            	unlink($filepath);
                echo "El archivo se ha eliminado correctamente. ";
    	    }else { 
                echo "El archivo no existe, no se puede eliminar.";
            }

        }
    }

    $bandera = true;
    $aux2 = true;
    $bandera2 = true;

    do {

        //Se debe ejecutar dos veces no se porque 
        if($bandera){
            $agi->stream_file("primero"); //reproduce primer audio
            $bandera = false;
        }else{
            $agi->stream_file("primero"); //reproduce primer audio
            $agi->exec("Playback","primero") ; 
        }

        // Pedir la opción del usuario antes de entrar al menú
        $_result = $agi->get_data('beep', 50000, 20);
        $option = $_result['result'];

        if($option == '4'){
            $bandera2 = false;
        }

        echo "resultado es $option";
        // Entrar al menú mientras el usuario no elija salir (opción 4)
        while ($option != '4') {

            if($aux2){

                echo "Pide los datos";
                $agi->stream_file("segundo"); //reproduce segundo audio

                $teclas_a_letras = [
                    '2' => ['A', 'B', 'C'],
                    '3' => ['D', 'E', 'F'],
                    '4' => ['G', 'H', 'I'],
                    '5' => ['J', 'K', 'L'],
                    '6' => ['M', 'N', 'O'],
                    '7' => ['P', 'Q', 'R', 'S'],
                    '8' => ['T', 'U', 'V'],
                    '9' => ['W', 'X', 'Y', 'Z'],
                ];
        
                // Se espera la entrada del usuario
                $_result = $agi->get_data('beep', 10000, 50);  // Espera hasta 5 segundos para que el usuario ingrese hasta 20 teclas
                $agi->verbose("Resultado de GET DATA: " . json_encode($_result), 1);
                $keys = $_result['result'];  // Las teclas presionadas
                $agi->verbose("Teclas presionadas: " . $keys, 1);
        
                // Inicializamos la variable de letras seleccionadas
                $letters = '';
                
                // Recorremos las teclas presionadas
                $last_key = null;  // Tecla actual
                $press_count = 0;  // Número de veces que la misma tecla ha sido presionada
        
                foreach (str_split($keys) as $key) {
                    if ($key == $last_key) {
                        // Si la tecla es la misma que la anterior, incrementamos el contador
                        $press_count++;
                    } else {
                        // Si la tecla cambia, restablecemos el contador y guardamos la letra seleccionada
                        if ($last_key !== null) {
                            // Seleccionamos la letra basada en las repeticiones
                            $letters .= $teclas_a_letras[$last_key][($press_count - 1) % count($teclas_a_letras[$last_key])];
                        }
                        // Reseteamos para la nueva tecla
                        $last_key = $key;
                        $press_count = 1;  // El primer toque de una tecla cuenta como 1
                    }
                }
                
                // Agregar la última tecla presionada después de terminar el bucle
                if ($last_key !== null) {
                    $letters .= $teclas_a_letras[$last_key][($press_count - 1) % count($teclas_a_letras[$last_key])];
                }
                $ciudad_obtenida = $letters;
                echo "Ciudad obtenida $ciudad_obtenida";
                $aux2 = false;
            }

            // Después de ejecutar una opción, volver a mostrar el menú
            $_result = $agi->get_data('beep', 10000, 50);
            $option = $_result['result'];

            switch ($option) {
                case '1':
                    $obtener_clima = obtenerClima($ciudad_obtenida);
		            $mensaje_clima = "La ciudad de $ciudad_obtenida tiene un clima ". $obtener_clima["weather"][0]["description"];

		            // Mandar el mensaje a la función generarAudio
		            generarAudio($mensaje_clima,$agi);
                    break;

                case '2': // Mostrar temperaturas
                    $obtener_temperaturas = obtenerClima($ciudad_obtenida);

                    $temp_min = mb_convert_encoding($obtener_temperaturas["main"]["temp_min"], 'UTF-8', 'auto');
                    $temp_max = mb_convert_encoding($obtener_temperaturas["main"]["temp_max"], 'UTF-8', 'auto');

                    echo utf8_encode("La temperatura máxima es: $temp_max K y la mínima es: $temp_min K.");

                    $mensaje_temparatura = utf8_encode("La temperatura máxima es $temp_max grados celsius y la mínima es $temp_min grados celsius");
                    generarAudio($mensaje_temparatura,$agi);

                    break;
                case '3':
                    $obtener_posicion_geografica = obtenerClima($ciudad_obtenida);
    
                    $latitud = mb_convert_encoding($obtener_posicion_geografica["coord"]["lat"],'UTF-8', 'auto');
                    $longitud =  mb_convert_encoding($obtener_posicion_geografica["coord"]["lon"],'UTF-8', 'auto');
                    
                    echo utf8_encode("Posición geográfica: Latitud $latitud, Longitud $longitud.");
                    
                    $mensaje_posicion =  utf8_encode("Posición geográfica de es Latitud $latitud, y Longitud es $longitud.");
                    generarAudio($mensaje_posicion,$agi);
                    break;

                case '4':
                    echo "Saliendo del sistema...";
                    $aux2 = true; // Reinicia la variable para que vuelva a pedir la ciudad en el próximo ciclo
                    break 2; // Sale del switch y del while, rompiendo ambos ciclos.
            }
        }

    } while ($bandera2);
    $agi->hangup();
?>
