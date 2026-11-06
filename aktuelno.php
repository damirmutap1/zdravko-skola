<?php require_once __DIR__ . '/includes/app.php'; cms_maybe_render_managed_page(pathinfo(__FILE__, PATHINFO_FILENAME), ['source_file' => __FILE__]); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">


    <title>Основна школа Здравко Јовановић </title>
    

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700&amp;subset=cyrillic" rel="stylesheet">   
    <link href="https://fonts.googleapis.com/css?family=PT+Sans:400,700&amp;subset=cyrillic-ext" rel="stylesheet"> 
  <link href="https://fonts.googleapis.com/css?family=Alice&amp;subset=cyrillic,cyrillic-ext" rel="stylesheet"> 
    
    
    
     <link href="stylesheets/screen.css" rel="stylesheet">
    <link href="css/tables.css" rel="stylesheet">
    
        <!-- OWL SLIDER PLUGIN -->
    <link rel="stylesheet" href="css/owl.carousel.css">
    <link rel="stylesheet" href="css/owl.theme.default.css">
    
</head>
    <style>
        a.aktuelno{color: #000; text-decoration: none;}
        a.aktuelno:hover{color:#606060}
        hr{height: 1px;border: none; color: #000; background-color: #000}
    </style>

<body>

     <?php include('includes/header.php')?>
    
   <div class="clearfix"></div>
    
    
    <section class="cover-istorijat">
       <div class="wrap">
           <img src="images/istorijat.jpg"> 
       </div>
    </section>
    
    
    <section class="single-page">
        <div class="wrap">         
            <h1><span>Актуелне вести</span></h1>
            
            <div class="single-box" style="margin-bottom:20px">
                
            <a href="aktuelno-decija-nedelja-5-9-oktobar.php" class="aktuelno">
                <h2 id="direktor">Дечија недеља - „Подељена срећа, два пута је већа“</h2>
                <span class="datum">Октобар од 05. до 09. 2020</span>
                <p>Ове школске године од 5. до 9. октобра, одржана је Дечија недеља под слоганом „Подељена срећа, два пута је већа“. </p>
            </a>      
             
                 <hr> 
                
            <a href="aktuelno-skola-da.php" class="aktuelno">
                <h2 id="direktor">"Школа ДА, дрога НЕ"</h2>
                <span class="datum">октобар 09.</span>
                <p>Предавање-едукација за ученике 7. и 8. разреда у оквиру пројекта "Школа ДА, дрога НЕ" одржано 19.03.2019. године.</p>
            </a>      
            
                 <hr> 
                
            <a href="aktuelno-osmomartovksa-radionica.php" class="aktuelno">
                <h2 id="direktor">Осмомратовска радионица са мамама и бакама</h2>
                <span class="datum">март 08.</span>
                <p>Осмомратовска радионица са мамама и бакама u ИО Стубо у организацији учитељице Горице Лазић</p>
            </a>  
                
            <hr> 
            <a href="aktuelno-izlozba-posvecena-alasu.php" class="aktuelno">
                <h2 id="direktor">Изложба посвећена Михаилу Петровићу Аласу</h2>
                <span class="datum">децембар 4.</span>
                <p>Приредба добродошлице за ђаке праваке и отварање Дечје недеље</p>
            </a>                  
                
            <hr> 
            <a href="aktuelno-festival-nauke.php" class="aktuelno">
                <h2 id="direktor">Фестивал науке у Београду</h2>
                <span class="datum">новембар 29.</span>
                <p>У четвртак, 29.11.2018.г. ученици осмог разреда су са наставником хемије посетили 12. </p>
            </a>  
                
            
            <hr> 
            <a href="aktuelno-decja-nedelja-flajer.php" class="aktuelno">
                <h2 id="direktor">План активности за дечју недељу</h2>
                <span class="datum">октобар  1. - 7. </span>
                <p>Моје је право да живим срећно и здраво - за одрастање без насиља</p>
            </a>
            <hr> 
                
            <a href="aktuelno-decja-nedelja-flajer.php"  class="aktuelno">
                <h2 id="direktor">Отварање и активности дечје недеље </h2>
                <span class="datum">октобар  1. - 7. </span>
                <p>Приредба добродошлице за ђаке праваке и отварање Дечје недеље</p>
            </a>
                
            <hr>    
                
            
            </div>
         
           
             <div class="clearfix"></div>
            
        </div>
    </section>
    
    

    <div class="clearfix"></div>
    
   <?php include('includes/footer.php')?>   
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/owl-slider/owl.carousel.min.js"></script>
    <script>
        var w, pos;
        
        
        /*Inicializuj poziciju okvira aktivnog meni bara*/
        $( document ).ready(function() {             
                pos = $(".main_nav.activ").position().left;
                w = $(".main_nav.activ").width();

                $("a span.activ:nth-child(1)").css("left",pos+5);
                $("a span.activ:nth-child(2)").css("left",pos+w+50);
                $("a span.activ:nth-child(3)").css("left",pos+w+50);
                $("a span.activ:nth-child(4)").css("left",pos+5);
           
        });
        /*Na resize prati poziciju aktivnog dugmeta*/
        $( window ).resize(function() {
                pos = $(".main_nav.activ").position().left;
                w = $(".main_nav.activ").width();

                $("a span.activ:nth-child(1)").css("left",pos+5);
                $("a span.activ:nth-child(2)").css("left",pos+w+50);
                $("a span.activ:nth-child(3)").css("left",pos+w+50);
                $("a span.activ:nth-child(4)").css("left",pos+5);
        });
    
        /*hover menu efekat*/    
        $(".main_nav").hover(function(){
            pos = $(this).position().left;
            w = $(this).width();

            $("a span.in-active:nth-child(1)").css("left",pos+5);
            $("a span.in-active:nth-child(2)").css("left",pos+w+50);
            $("a span.in-active:nth-child(3)").css("left",pos+w+50);
            $("a span.in-active:nth-child(4)").css("left",pos+5);
            
        });
        
        $(".button a").hover(function(){
            pos = $(this).position().left;
            w = $(this).width();

            $("a span.buton:nth-child(1)").css("left",pos);
            $("a span.buton:nth-child(2)").css("left",pos+w+16);
            $("a span.buton:nth-child(3)").css("left",pos+w+16);
            $("a span.buton:nth-child(4)").css("left",pos);
            
        });
       

        
        
      $('.main-slider').owlCarousel({            
            nav:false,
            responsiveClass:true,
            dots:false,
            responsive:{
                0:{
                    items:1
                },
                600:{
                    items:1
                },
                1000:{
                    items:1
                }
            }
        });
        
        $('.galerija').owlCarousel({            
            nav:false,
            responsiveClass:true,
            dots:false,
            responsive:{
                0:{
                    items:2
                },
                600:{
                    items:4
                },
                1000:{
                    items:4
                }
            }
        });  
</script>
    
<script>
$(document).ready(function(){
  // Add smooth scrolling to all links
  $("a").on('click', function(event) {

    // Make sure this.hash has a value before overriding default behavior
    if (this.hash !== "") {
      // Prevent default anchor click behavior
      event.preventDefault();

      // Store hash
      var hash = this.hash;

      // Using jQuery's animate() method to add smooth page scroll
      // The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area
      $('html, body').animate({
        scrollTop: $(hash).offset().top
      }, 800, function(){
   
        // Add hash (#) to URL when done scrolling (default click behavior)
        window.location.hash = hash;
      });
    } // End if
  });
});
</script>
    
    
    
</body>
</html>
