var i;
function show_anexos(str_anexos) {
    if (str_anexos != null) {
        var html_anexos = '';
        var arr = str_anexos.split(',');
        for (var i = 0; i < arr.length; i++) {
            var file_name = arr[i];
            if (file_name != "") {
                html_anexos += '<div id="imageNo' + i + '" class = "col-lg-4 listitemClass"><img class="img-fluid" src="' + file_name + '" title="' + file_name.split('/').pop() + '"><i title="Apagar" style="cursor:pointer;" class="bx bx-trash btn-del" data-idx="' + i + '"></i></div>';
            }
        }
        $('#imageListId').html(html_anexos);
    }
}

window.onload = function () {

    show_anexos($('#post_upload_0').val());

    $("#imageListId").sortable({
        update: function (event, ui) {
            var values = [];
            var new_string_anexos = '';
            $('.listitemClass').each(function (index) {
                values.push($(this).attr("id").replace("imageNo", ""));
                console.log(values)
            });
            for (var i = 0; i < values.length; i++) {
                new_string_anexos += $('#imageNo' + values[i] + ' img').attr('src') + ',';
            }
            str_anexos = new_string_anexos;
            console.log(str_anexos);
            $('#post_upload_0').val(str_anexos);
        }
    });

    $(window).scroll(function () {
        if ($(this).scrollTop() > 200) {
            $('#scrollToTop').fadeIn();
        } else {
            $('#scrollToTop').fadeOut();
        }
    });

    $('#scrollToTop').click(function () {
        $('html, body').animate({ scrollTop: 0 }, 500);
        return false;
    });

    $(document).on('click', '.btn-del', {}, function (e) {
        if (confirm("Apagar essa foto ?")) {
            var idx = $(this).attr("data-idx");
            str_anexos = $('#post_upload_0').val();
            var arr = str_anexos.split(',');
            console.log(str_anexos);
            var new_string_anexos = '';
            for (var i = 0; i < arr.length; i++) {
                if (i != idx) {
                    if (new_string_anexos != '') {
                        new_string_anexos += ',';
                    }
                    new_string_anexos += arr[i];
                }
            }
            str_anexos = new_string_anexos;
            console.log(str_anexos);
            show_anexos(str_anexos);
            $('#post_upload_0').val(str_anexos);
        }
    });
}

function upload_image(type, val) {
    aw_uploader = wp.media({
        title: 'Inserir Anexo',
        library: {
            uploadedTo: wp.media.view.settings.post.id
        },
        button: {
            text: 'Usar esse arquivo'
        },
        multiple: true
    }).on('select', function () {
        var attachment = aw_uploader.state().get('selection').first().toJSON();
        var url = attachment.url.replace(window.location.origin, '').trim();
        if (type == 1) { //settings            
            $('#portal_input_' + val).val(url);
            $('#preview_portal_input_' + val).attr('src', url);
        }
        if (type == 2) { //post upload 
            var x = "";
            var str_anexos = $('#post_upload_' + val).val();
            if (str_anexos != "") x = ",";
            str_anexos += x + url;
            console.log(str_anexos);
            $('#post_upload_' + val).val(str_anexos);
            show_anexos(str_anexos);
        }
    }).open();

}