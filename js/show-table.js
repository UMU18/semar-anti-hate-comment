$ = jQuery;
$(document).ready(function() {
 
   $('#hateTable').dataTable({
   		'columnDefs': [{
        'targets': 0,
        'searchable': false,
        'orderable': false,
      }],
      'order': [[1, 'asc']]
   });

   $('#table-select-all').on('click', function() {
   		if($(this).is(':checked',true)) {
   			$(".mydata_checkbox").prop('checked', true);
		}
		else {
			$(".mydata_checkbox").prop('checked',false);
		}
	});

	$('#change').on('click',function(e){
		e.preventDefault();
		var value = $('select#hateTableSelector').find(':selected').data('selected');
		var mydata = [];
		var mycategorydata = [];
		
		$('.mydata_checkbox:checked').each(function(i) {  
			mydata[i] = $(this).val();
			mycategorydata[i] = $('p#id'+mydata[i]).attr('class');
		});

		if(mydata.length <=0) { alert("Please select records."); }
		else{
			
			 $.ajax({ 
        		type: "POST", 
        		url: "anti-hate-comment.php",
        		data: {mydata:mydata,value:value},
        		success: function(response) {
        			var values='';
						if(value==1){
							values='Safe';
						}
						else{
							values='Not Safe';
						}
        			for (var i=0; i < mydata.length; i++ ){
        				$('p#id'+mydata[i]).html(values);
        				if(mycategorydata[i]!='category'+value){
        					$('p#id'+mydata[i]).addClass('category'+value).removeClass(mycategorydata[i]);
        				}	
        			}
        		},
        		error: function(xhr) {
        		alert('Request Status: ' + xhr.status + ' Status Text: ' + xhr.statusText + ' ' + xhr.responseText);

      			}    
			}); 
		}
       

    });


});