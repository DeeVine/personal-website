
<!doctype html>
<html>
<head>
    <title>Weather Scraper</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">

    <meta charset="utf-8" />
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>

<style type="text/css">

    html, body{
        height:100%;
    }

    .container{
        background-image:url(images/lake.jpg);
        width:100%;
        height:100%;
        background-size:cover;
        background-position:center;
        padding-top: 150px;
    }

    .center{
        text-align:center;

    }

    .white{
        color:rgb(108, 108, 108);
    }

    p{
        padding-top:15px;
        padding-bottom:15px;
    }

    button{
        margin-top:20px;
        margin-bottom:20px;
    }

    .alert{
        margin-top:20px;
        display: none;
        
    }

    #success{
        display:none;
    }

    #fail{
        display:none;
    }


</style>    

</head>

<body>


    <div class="container">

        <div class="row">

            <div class="col-md-6 col-md-offset-3 center">
                                    
                    <h1 class="center white">Weather Predictor</h1>
                    
                    <p class="center white">Enter your city below to get a forecast for weather.</p>
                   
                    <form>

                        <div class="form-group">
                          
                            <input type="text" class="form-control" name="city" id="city" placeholder="San Jose, San Francisco, Palo Alto, Etc.">
                         
                        </div>

                        <button id="findMyWeather" type="submit" class="btn btn-success btn-lg">Find my Weather</button>
                   
                    </form>
                    
            </div>
            
            <div id="success"class="col-md-6 col-md-offset-3 alert alert-success">
                    Success!
            </div> 

             <div id="fail" class="col-md-6 col-md-offset-3 alert alert-danger">
                    Couldn't find city!
            </div> 

            <div id="noCity" class="col-md-6 col-md-offset-3 alert alert-danger">
                    Please input a city.
            </div>
            

        </div>

    </div>
    
<script>
    
    $("#findMyWeather").click(function(event){

        event.preventDefault();

        $(".alert").hide();

        if ($("#city").val()!=""){
            $.get("scraper.php?city="+$("#city").val(), function(data){

                if(data==""){

                    $("#fail").fadeIn();
                   

                } else{
                    $("#success").html(data).fadeIn();
                   

                }

            });
        }
        
        else{
           
            $("#noCity").fadeIn();
         
        }
    });

        
</script>

</body>
</html>
