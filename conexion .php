<?php

$host='localhost';
$bd='empresa';
$user='postgres';
$pass='1234';

$nom=$_POST['nom'];
$email=$_POST['email'];
$tel=$_POST['tel'];
$direc=$_POST['direc'];

$conexion=pg_connect("host=$host dbname=$bd user=$user password=$pass");

$query=("INSERT INTO clientes(nombre,correo,telefono,direccion)
	VALUES('$nom','$email','$tel','$direc')");

$consulta=pg_query($conexion,$query);



echo "Estos datos fueron almacenados en la base de datos: <br> Nombre:".$nom."<br>"."Correo: ".$email.
"<br> Teléfono: ".$tel."<br> Dirección: ".$direc;

pg_close();

?>