try {
    var body = $("body"),
        doc = $(document),
        modalId = "mg-trans",
        triggerClass = "mg-trans-trigger",
        modals = "#" + modalId,
        drops = ".modal-backdrop";

    var prettify = function(str) {
        return str.replace( /([a-z])([A-Z])/g, '$1-$2' ).toLowerCase();
    };

    doc.ready(function () {

        var triggerData = doc.find(".mg-trans-data");

        if (triggerData.length > 0) {

            body.append('<a href="#" id="translator-trigger" class="off">Translation: <i class="fa fa-toggle-on"></i><i class="fa fa-toggle-off"></i></a>');

            triggerData.each(function () {

                $(this).css({
                    "color": "inherit",
                    "cursor" : "pointer",
                    "padding" : "0",
                    "display" : "inline-block"
                });

                var anchor = $(this).parents("a"),
                    triggerDataElement = $(this);

                //try to find a button instead if we did not find any anchors
                if(typeof anchor === "undefined" || anchor.length < 1){
                    anchor = $(this).parents("button");
                }

                var wrapper = '<a class="'+triggerClass+'"';

                $.each(triggerDataElement.data(), function(key, value){
                    wrapper = wrapper + ' data-'+prettify(key)+'="'+value+'"';
                });

                wrapper = wrapper + '></a>';

                //if there is any anchors add data to it and classes necessary to trigger the translation update modal
                if(typeof anchor !== "undefined" && anchor.length > 0){

                    $.each(triggerDataElement.data(), function(key, value){
                        anchor.attr("data-"+prettify(key), value);
                    });

                    anchor
                        .addClass(triggerClass)
                        .addClass("mg-trans-anchor-origin");

                }else{
                    //wrap the text with an anchor to trigger the translation update model if it doesn't parent an anchor
                    triggerDataElement.next("span").wrap(wrapper);
                }
            });

            body.append('<input type="hidden" name="trans-cache-clear" id="trans-cache-clear"/>');
            body.append('<input type="hidden" name="trans-locale" id="trans-locale"/>');
        }
    });

    doc.on("click", "#translator-trigger", function(e){
        e.preventDefault();

        if($(this).hasClass("off")) {
            $("."+triggerClass).each(function(){
                $(this).addClass(triggerClass+"-active");
            });
            $(this).removeClass("off").addClass("on");
        }else{
            $("."+triggerClass).each(function(){
                $(this).removeClass(triggerClass+"-active");
            });
            $(this).removeClass("on").addClass("off");
        }
    });

    doc.on("click", ".mg-trans-trigger-active", function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        modalTrigger($(this));
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
                        console.log(data);
                        textBuilder(data, doc.find("#trans-locale").val());
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

        var me = element,
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
    }, textBuilder = function(text, locale){
        doc.find("#trans-"+text._key.replace(" ", "-")+"-X-"+text._domain.replace(" ", "-"))
            .html(text[locale]);
        $("#mgUIBlock").remove();
    };

}catch(e){
    console.log(e);
}
