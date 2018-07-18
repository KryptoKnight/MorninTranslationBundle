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

            body.append('<input type="hidden" name="trans-cache-clear" id="trans-cache-clear"/>');
            body.append('<input type="hidden" name="trans-locale" id="trans-locale"/>');
            modalTrigger(triggers);
        }
    });

    doc.on("click", ".trans-update", function (e) {
        e.preventDefault();

        var me = $(this),
            content = me.parents("form").serialize();

        $.ajax({
            url : me.data("action"),
            method: "PUT",
            data: content,
            dataType: "json",
            success: function(data){

                doc.find(modals).modal("hide");

                $.ajax({
                    url: doc.find("#trans-cache-clear").val(),
                    method: "GET",
                    dataType: "json",
                    success: function(){
                        textBuilder(data, $("#trans-locale").val());
                    }
                })
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
                '                    <div class="modal-body">\n' +
                '                        <strong>Key:&nbsp;</strong>' +
                '                        <span>'+key+'</span><br/>' +
                '                        <strong class="last-child">Domain:&nbsp;</strong>' +
                '                        <span>'+domain+'</span><br/>';
            for(var locale = 0; locale < locales.length; locale++){

                var val = null;
                if(typeof content[locales[locale]] !== "undefined"){
                    val = content[locales[locale]];
                }
                modalContainer += '<label for="trans-'+locales[locale]+'">'+locales[locale]+':&nbsp;</label>';
                modalContainer += '<textarea name="'+locales[locale]+'" id="trans-'+locales[locale]+'">'+val+'</textarea><br/>';
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

            doc.find(modals).on('hidden.bs.modal', function(e) {
                doc.find(modals).remove();
            });

    }, modalTrigger = function(element){

        element.click(function(e){
            e.preventDefault();
            e.stopPropagation();

            var me = $(this),
                key = me.data("transKey"),
                domain = me.data("transDomain"),
                ajaxGet = me.data("transAjaxGet"),
                ajaxSet = me.data('transAjaxSet'),
                ajaxCache = me.data('transAjaxCacheClear'),
                locales = me.data("transLocales").split(","),
                currentLocale = me.find("span").data("locale");

            $("#trans-cache-clear").val(ajaxCache);
            $("#trans-locale").val(currentLocale);

            $.ajax({
                url: ajaxGet + "/" + key + "/" + domain,
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
                error: function () {
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
    }, textBuilder = function(text, locale){
        doc.find("#trans-"+text._key.replace(" ", "-")+"-X-"+text._domain.replace(" ", "-"))
            .text(text[locale]);
    };

}catch(e){
    console.log(e);
}
