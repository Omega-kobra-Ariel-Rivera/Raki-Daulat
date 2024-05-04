$('#Enviar').click(function(){

	nom= $('#nombre').val();
	email= $('#correo').val();
	tel=$('#telefono').val();
	direc=$('#direccion').val();
	$.ajax({

		url:'conexion.php',
		type:'post',
		data:{nom:nom,email:email,tel:tel,direc:direc},
		success:function(respuesta){

			$('#resultado').html(respuesta);
			$('#nombre').val(' ');
			$('#correo').val(' ');
			$('#telefono').val(' ');
			$('#direccion').val(' ');


			Swal.fire({
				position: 'top-center',
				icon: 'success',
				title: 'Los datos fueron guardados exitosamente.',
				showConfirmButton: false,
				timer: 5000
			});
			boton = document.getElementById('boton1');
            boton.style.display = '';

		$('#boton1').click(function(){

			$('#resultado').html('');
			boton.style.display='none';
		})

		}
			


	})
})




