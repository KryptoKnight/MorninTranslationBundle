window.onload = function() {
    if (!window.jQuery) {
        alert("You don't have jquery installed");
    }
}

try {
    var body = $("body"),
        doc = $(document),
        modalId = "mg-trans",
        modals = "#" + modalId;

    doc.ready(function () {

        var triggers = $(".mg-trans-trigger");
        if (triggers.length > 0) {
            triggers.each(function () {
                $(this).css({
                    "color": "inherit"
                })
            });
        }

        modalTrigger(triggers);
    });

    doc.on("click", ".trans-update", function (e) {
        e.preventDefault();

        var me = $(this),
            locales = me.data("transLocales").split(","),
            content = me.parents("form").serialize();


        $.ajax({
            url : me.data("action"),
            method: "PUT",
            data: content,
            dataType: "json",
            success: function(data){
                console.log(data);
                doc.find(modals).modal("hide");
            }
        })
    });

    var modalBuilder = function(key, domain, locales, content, ajax){

            if(typeof content.id !== "undefined"){
                ajax = ajax.replace("-id-", content.id);
            }

            doc.find(modals).remove();

            var modalContainer =
                '<div class="modal" tabindex="-1" role="dialog" id="'+modalId+'">\n' +
                '          <form>\n' +
                '            <div class="modal-dialog" role="document">\n' +
                '                <div class="modal-content">\n' +
                '                    <div class="modal-header">\n' +
                '                        <h5 class="modal-title">Translation Title</h5>\n' +
                '                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">\n' +
                '                            <span aria-hidden="true">&times;</span>\n' +
                '                        </button>\n' +
                '                    </div>\n' +
                '                    <div class="modal-body">\n' +
                '                        <strong>Key:&nbsp;</strong>' +
                '                        <span>'+key+'</span><br/>' +
                '                        <strong>Domain:&nbsp;</strong>' +
                '                        <span>'+domain+'</span><br/>';
            for(var locale = 0; locale < locales.length; locale++){

                var val = null;
                if(typeof content[locales[locale]] !== "undefined"){
                    val = content[locales[locale]];
                }
                modalContainer += '<label for="trans-'+locales[locale]+'">'+locales[locale]+':&nbsp;</label>';
                modalContainer += '<input type="text" name="'+locales[locale]+'" id="trans-'+locales[locale]+'" value="'+val+'"/><br/>';
            }
            modalContainer += '  </div>\n' +
                '                    <div class="modal-footer">\n' +
                '                        <button type="button" class="btn btn-primary trans-update" data-action="'+ajax+'" data-trans-locales="'+locales.join(",")+'">Save changes</button>\n' +
                '                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>\n' +
                '                    </div>\n' +
                '                </div>\n' +
                '            </div>\n' +
                '          </form>\n' +
                '        </div>';

            body.append(modalContainer);
            doc.find(modals).modal();

            doc.find(modals).on('hidden.bs.modal', function (e) {
                doc.find(modals).remove();
            });

    }, modalTrigger = function(element){

        element.click(function(e){
            e.preventDefault();
            e.stopPropagation();

            var me = $(this),
                key = me.data("transKey"),
                domain = me.data("transDomain"),
                ajaxCall = me.data("transAjaxGet"),
                ajaxSet = me.data('transAjaxSet'),
                ajaxCache = me.data('transAjaxCacheClear'),
                locales = me.data("transLocales").split(",");
            
            body.append('<input type="hidden" name="trans-cache-clear" id="trans-cache-clear" value="'+ajaxCache+'"');

            $.ajax({
                url: ajaxCall + "/" + key + "/" + domain,
                method: "GET",
                success: function (content) {
                    modalBuilder(
                        key,
                        domain,
                        locales,
                        content,
                        ajaxSet
                    );
                },
                error: function (error) {
                    modalBuilder(
                        key,
                        domain,
                        locales,
                        [],
                        ajaxSet
                    )
                }
            });
        });
    }

}catch(e){
    console.log(e);
}
