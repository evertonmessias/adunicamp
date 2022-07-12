window.onload = function () {

    $('.venobox').venobox();

    $('.display').hide();
    $('.btn-album').click(()=>{
        $('.display').show();
    })

    $(document).on('click', '.btn-accordion', {}, function (e) {       
        var sec = $(this).attr('data-section');
        var acc = $(this).attr('data-accordion');
        if (!$('.block' + sec + acc).is(":visible")) {         
            $('.block' + sec).slideUp(500);
            $('.btn-accordion' + sec).removeClass('btn-accordion-active');
            $('.btn-accordion' + sec + acc).addClass('btn-accordion-active');         
            $('.block' + sec + acc).slideDown(500);              
        }else { 
            $('.btn-accordion' + sec + acc).removeClass('btn-accordion-active'); 
            $('.block' + sec + acc).slideUp(500);             
        }
    });
}