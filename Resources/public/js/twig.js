try {
    var body = $("body"),
        doc = $(document),
        modalId = "mg-trans",
        modals = "#" + modalId,
        drops = ".modal-backdrop";

    doc.ready(function () {

        body.append('<a href="#" id="translator-trigger" class="off">Translation: <i class="fa fa-toggle-on"></i><i class="fa fa-toggle-off"></i></a>');

        var triggers = doc.find(".mg-trans-trigger");
        if (triggers.length > 0) {
            triggers.each(function () {

                $(this).css({
                    "color": "inherit",
                    "cursor" : "pointer",
                    "padding" : "0",
                    "display" : "inline-block"
                });

                var anchor = $(this).parents("a"),
                    trigger = $(this);

                if(typeof anchor !== "undefined"){
                    var cls = anchor.attr("class"),
                        txt = trigger.parent().html();

                    cls = (typeof cls === "undefined")? "" : cls;

                    anchor.replaceWith('<div class="mg-trans-anchor '+cls+'">'+txt+'</div>');
                }
            });

            body.append('<input type="hidden" name="trans-cache-clear" id="trans-cache-clear"/>');
            body.append('<input type="hidden" name="trans-locale" id="trans-locale"/>');
            doc.find("#translator-trigger").removeClass("off").addClass("on");
            modalTrigger(doc.find(".mg-trans-trigger"));
        }
    });

    doc.on("click", "#translator-trigger", function(e){
        e.preventDefault();

        var key = encodeURI("translator")
            , value = encodeURI("true")
            , url = null
            , redirect = null
            , href = location.href;

        if($(this).hasClass("off")) {

            if(href.indexOf("?") === -1){
                url = "?" + key + "=" + value;
            }else{
                url = "&" + key + "=" + value;
            }

            redirect = href + url;
            window.location = redirect;
        }else{
            url = key + "=" + value;

            if(href.indexOf("?"+url) !== -1){
                redirect = href.replace("?"+url, "");
                window.location = redirect;
            }else{
                url = "&" + key + "=" + value;
                redirect = href.replace(url, "");
                window.location = redirect;
            }


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
                doc.find(drops).remove();
                doc.find("body").css({ "padding-right" : 0});
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
            .html(text[locale]);
    };

}catch(e){
    console.log(e);
}
