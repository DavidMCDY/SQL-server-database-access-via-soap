<?php
	/*
	Version de PHP: 7.1.9, Ultima modificacion: 03/11/2017

	Drivers de SQL Server (Version 4.3): https://www.microsoft.com/en-us/download/details.aspx?id=55642
	Listado de los requerimientos y versiones de PHP para los Drivers de SQL: https://docs.microsoft.com/en-us/sql/connect/php/system-requirements-for-the-php-sql-driver
	En el primer comentario del foro, en el apartado "3", se encuentran unas fotos sobre como configuar el archivo PHP.ini para activar los drivers.
	En el siguiente enlace: https://stackoverflow.com/questions/32938711/php-7-unable-to-initialize-sqlsrv

	Metodo para configurar php como una variable de entorno: 
	Mediante este metodo podemos configurar la ruta donde se tiene instalado php como una variable de entorno de modo que podremos hacer referencia a ella usando solo la palabra "php" en vez de la ruta completa.
	-Configuracion de las variables de entorno en windows:
		En la ruta Equipo->Propiedades->Configuracion avanzada del sistema, se abrira una pestaña con titulo "Propiedas del sistema" hay dentro en "Opciones avanzadas" abrimos "variables de entorno", el cual se encuentra en la parte inferior de esta pestaña. En la nueva pestaña "Variables de entorno", dentro del cuadro de "Variables del sistema" damos doble click en "Path".
		Dentro de la nueva ventana llamada "Editar variables de entorno" le damos a examinar y añadimos la ruta donde se encuentra php, es decir la carpeta contenedora de php.exe en el caso de que hayas tenido una instalacion con los valores predeterminados será "C:\XAMPP\php".
	-Configuracion de las variables de entorno en linux:
		Usando el comando export "nombre de la variable"="ruta", es decir en el caso de que quieras llamar a la variable "php", export php="ruta donde se tiene php"

	Llamada al script mediante consola:
	-En caso de haberse usado variables de entorno:
		php "Ruta donde se encuentra el script" "Nombre del servidor" "Nombre de la base de datos" "Ruta donde quieras que se cree el archivo generado"
	-En caso de no haberse usado variables de entornp:
		"Ruta donde se encuentra php.exe" "Ruta donde se encuentra el script" "Nombre del servidor" "Nombre de la base de datos" "Ruta donde quieras que se cree el archivo generado"
	*/
	if($argc!=4)
		echo "Error! Metodo de llamada: php script.php 'Servidor' 'Base de Datos' 'Ruta a guardar el archivo'";
	else{


		try{
			$con = new PDO("sqlsrv:Server=".$argv[1].";Database=".$argv[2],NULL,NULL); //Acceso a el Servidor SQL.
			$con->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}

		catch(PDOException $err){
			die("Error connecting to SQL Server");
		}

		$query = "select * from [dbo].direcciones_normalizacion_webservice where FLAG_ENVIADO=0"; //Llamada a la base de datos.
		$stmt = $con->query($query);

		$i=0;

		while ($row[]=$stmt->fetch(PDO::FETCH_ASSOC)){ //En row[] se almacena un array para cada fila de la llamada a la base de datos en la que el indice es el nombre de la columna 
	    	$i++;                                      //es decir para codigo postal es row['numero de fila']['nombre de la columna'], siendo en referencia a la tabla.
	   	}

	   	$soapCl = new SoapClient('https://ws.deyde.com.es/deyde-ws/services/deyde?wsdl', array('trace' => true, 'login'=> "COGNGUAN", 'password'=>"MATTN606")); //Llamada al webservice

	   	$fecha = date("YmdHis_Ymd");//Recogida de la fecha y hora actual en el formato solicitado

	   	$f = fopen($argv[3].'/'.$fecha.'_fichero_generado.sal', "w"); //Creamos el fichero donde queremos escribir las direcciones normalizadas, el cual se creara en la ruta especifica en 
	   	for($j=0; $j<$i; $j++){										  //la linea de comandos
			$request="<soapenv:Envelope xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/' xmlns:web='http://microsoft.com/webservices/' xmlns:ns1='https://ws.deyde.com.es/deyde-ws/services/deyde?wsdl'>
								<soapenv:Header/>
								   <soapenv:Body>
								      <web:deydePlus>
								         <web:user>COGNGUAN</web:user>
								         <web:password>MATTN606</web:password>
								         <web:toNormalize>".$row[$j]['DIRECCION']." ".$row[$j]['CDPOSTAL']." ".$row[$j]['LOCALIDAD']." ".$row[$j]['PROVINCIA']."</web:toNormalize>
								         <web:productList>es,cod,dom</web:productList>
								         <web:errorMsg></web:errorMsg>
								         <web:candidateList></web:candidateList>
								         <web:selectedCandidate>-1</web:selectedCandidate>
								         <web:pobVia></web:pobVia>
								      </web:deydePlus>
								   </soapenv:Body>
						</soapenv:Envelope>";

			$response= $soapCl->__doRequest($request, 'https://ws.deyde.com.es/deyde-ws/services/deyde?wsdl', 'http://servermydataq:port/deyde-ws/services/deyde?wsdl', 1);
			//Recogida de la respuesta del webservice.
			$update = "update [dbo].direcciones_normalizacion_webservice set FLAG_ENVIADO=1 WHERE ID=".$row[$j]['ID'];
			$con->query($update);




			$arr = substr($response, 218, -224);
			$arr = utf8_decode($arr);
			$arr.= str_pad($row[$j]['MID'], 10);
			$arr.= str_pad(utf8_decode($row[$j]['DIRECCION']), 100);
			$arr.= str_pad($row[$j]['LOCALIDAD'], 50);
			$arr.= str_pad($row[$j]['PROVINCIA'], 50);
			$arr.= str_pad($row[$j]['CDPOSTAL'], 5);
			$arr.= str_pad("", 885);
			fwrite($f, $arr."\n");

		}
		fclose($f);
	}
?>