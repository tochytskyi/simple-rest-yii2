function doRequest() { 

    //var formData = new FormData();
    $.ajax({
        type: 'get',
        dataType: 'json',
        url: '/site/trip',
        success: function(data) {
            if (data.error) {
                alert('Error');
            } else {
                alert('Done');  
                console.log(data);
            }          
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert("Error occurred. Try again, please!");
        }
    }); 
}