jQuery(function($){
    function disabledInputForAbo()
    {
        let select = $('#computop_type').val();
            if(select === "2")
            {
                console.log('ok');
                $("#computop_number_periodicity").removeAttr('disabled');
                $("#computop_periodicity").removeAttr('disabled');
                $("#computop_number_occurrences").removeAttr('disabled');
                $("#computop_recurring_amount").removeAttr('disabled');
            }    
            else
            {
                $("#computop_number_periodicity").attr('disabled','disabled');
                $("#computop_periodicity").attr('disabled','disabled');
                $("#computop_number_occurrences").attr('disabled','disabled');
                $("#computop_recurring_amount").attr('disabled','disabled');
            }    
    }

jQuery(document).ready(function() {
    $('#delete_account').click( function( event ) {
     if( ! confirm( 'Please confirm delete account' ) ) {
         event.preventDefault();
     }           
    });
        
    if($("#computop_capture_method").children("option:selected").val() == "AUTO")
    {
        $("#computop_capture_hours").parent('.forminp').parent('tr').hide();
    }
    else
    {
        $("#computop_capture_hours").parent('.forminp').parent('tr').show(); 
    }    
    
    $("#computop_capture_method").change(function(){
        var text = $(this).children("option:selected"). val();
        if(text == "AUTO")
        {
            $("#computop_capture_hours").parent('.forminp').parent('tr').hide();
        }    
        else
        {
            $("#computop_capture_hours").parent('.forminp').parent('tr').show(); 
        }    
    });
    
    disabledInputForAbo();
    
    $("#computop_type").change(function(){
        disabledInputForAbo();
    });
    
 });
});