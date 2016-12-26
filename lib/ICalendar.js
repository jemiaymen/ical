	$(document).ready(function() {

		function updateIcal(){

			if ($('#rap').val()) {

				var data = { "id": $('#cid').val(),"title" : $('#tt').val() , "rappel" : $('#rap').val() , "start" : $('#start').val() , "end" : $('#end').val()};

			}else {
				var data = {"id": $('#cid').val(), "title" : $('#tt').val()  , "start" : $('#start').val() , "end" : $('#end').val()};
			}

			$.post('lib/ICalendar.php',data,function(result){
				console.log(result);
				$('#calendar').fullCalendar( 'refetchEvents' );
			});
			
		}

		function Export(token){
			window.location = 'lib/ICalendar.php?tk=' + token + '&exp=yes' ;
		}

		function Import(token){
			$("#fn").click();
			
		}

		$("#fn").change(function(evt){
			var token = 'token';
			var fd = new FormData();
			fd.append("fn", document.getElementById('fn').files[0]);
			var request = new XMLHttpRequest();
		
			request.onreadystatechange = function() {
			    if (request.readyState == XMLHttpRequest.DONE) {
			    	setTimeout(function () {
				        $('#calendar').fullCalendar( 'refetchEvents' );
				    }, 500);
			        
			    }
			}
			request.open("POST", "lib/ICalendar.php?tk=" + token);
			request.send(fd);

		});




		var tooltip = $('#calendar').qtip({
			id: 'calendar',
			prerender: true,
			content: {
				text: ' ',
				title: {
					button: true
				}
			},
			position: {
	            my: 'center',
			    at: 'center',
			    target: $(window)
	         },
			show: false,
			hide: false,
			style: 'qtip-light'
		}).qtip('api');

	
		$('#calendar').fullCalendar({
			customButtons: {
		        import: {
		            text: 'Import',
		            click: function() {
		                Import('token');
		            }
		        },
		        export : {
		        	text: 'Export',
		            click: function() {
						Export('token');
		            }
		        },
		    },
			header: {
				left: 'prev,next today , import , export',
				center: 'title',
				right: 'month,agendaDay,listWeek '
			},
			locale: 'fr',
			editable: false,
			navLinks: true, // can click day/week names to navigate views
			eventLimit: true, // allow "more" link when too many events
			events: {
				url: 'lib/ICalendar.php?tk=token',
				error: function() {
					$('#script-warning').show();
				}
			},
			loading: function(bool) {
				$('#loading').toggle(bool);
			},
			eventClick: function(data, event, view) {
				var content;

				if (data.role ==='admin') {
					content = '<center><input type="text" id="tt" class="nob tt" value="'+ data.title +'"></center>' ;
					content += '<p><b>Debut: </b><input type="datetime-local" id="start" class="nob" value="'+ moment(data.start).format() +'"></p>';
					content += '<p><b>Fin: </b><input type="datetime-local" id="end" class="nob" value="'+ moment(data.end).format() +'"></p>';
					content += '<p><b>ID: </b> '+ data.user +'</p>';

					if (data.rappel ) {
						content += '<p><b>Rappel: </b><input type="time" id="rap" class="nob" value="'+ data.rappel +'"></p>';
					}

					content += '<input type="hidden" id="cid" value="'+ data.id +'">'; 
					content += '<center><button id="save" >Enregister</button></center>';

				}else {
					content = '<h3>'+data.title+'</h3>' ;
					content += '<p><b>Debut: </b> '+ moment(data.start).calendar() +'<br />';
					content += '<p><b>Fin: </b> '+ moment(data.start).calendar() +'</p>';
					content += '<p><b>ID: </b> '+ data.user +'</p>';
					if (data.rappel ) {
						content += '<p><b>Rappel: </b> '+ data.rappel +'</p>';
					}
				}
				    
				tooltip.set({
					'content.text': content
				})
				.reposition(event).show(event);

				$('#save').click(function(){
					updateIcal();
					tooltip.hide();
				});
			},
			dayClick: function() { tooltip.hide() },
			viewDisplay: function() { tooltip.hide() },
		});
		
	});