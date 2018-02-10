/**
 * Created by zzpzero on 2017/6/13.
 */


function unpacker_filter(source) {
    var trailing_comments = '',
        comment = '',
        unpacked = '',
        found = false;

    // cut trailing comments
    do {
        found = false;
        if (/^\s*\/\*/.test(source)) {
            found = true;
            comment = source.substr(0, source.indexOf('*/') + 2);
            source = source.substr(comment.length).replace(/^\s+/, '');
            trailing_comments += comment + "\n";
        } else if (/^\s*\/\//.test(source)) {
            found = true;
            comment = source.match(/^\s*\/\/.*/)[0];
            source = source.substr(comment.length).replace(/^\s+/, '');
            trailing_comments += comment + "\n";
        }
    } while (found);

    var unpackers = [P_A_C_K_E_R, Urlencoded, /*JavascriptObfuscator,*/ MyObfuscate];
    for (var i = 0; i < unpackers.length; i++) {
        if (unpackers[i].detect(source)) {
            unpacked = unpackers[i].unpack(source);
            if (unpacked != source) {
                source = unpacker_filter(unpacked);
            }
        }
    }

    return trailing_comments + source;
}

function looks_like_html(source) {
    var trimmed = source.replace(/^[ \t\n\r]+/, '');
    var comment_mark = '<' + '!-' + '-';
    return (trimmed && (trimmed.substring(0, 1) === '<' && trimmed.substring(0, 4) !== comment_mark));
}

$.fn.extend({
    js_beautify: function () {
        var is_use_code_mirror = (!window.location.href.match(/without-codemirror/));
        return this.each(function () {
            var that = this;
            var default_text = "";
                //"// This is just a sample script. Paste your real code (javascript or HTML) here.\n\nif ('this_is'==/an_example/){of_beautifer();}else{var a=b?(c%d):e[f];}";

            var editor = null;

            if (is_use_code_mirror && typeof CodeMirror !== 'undefined') {
                editor = CodeMirror.fromTextArea($(that)[0], {
                    theme: 'default',
                    lineNumbers: true
                });
                editor.focus();

                editor.setValue(default_text);
                $(that).next('.CodeMirror').click(function () {
                    if (editor.getValue() == default_text) {
                        editor.setValue('');
                    }
                });
            } else {
                $(that).val(default_text).bind('click focus', function () {
                    if ($(this).val() == default_text) {
                        $(this).val('');
                    }
                }).bind('blur', function () {
                    if (!$(this).val()) {
                        $(this).val(default_text);
                    }
                });
            }
            that.editor = editor;
            that.beautify_in_progress = false;
            return that;
        });
    }
});


var beautify = function beautify(js_beautify_ob) {
    if (js_beautify_ob.beautify_in_progress) return;

    js_beautify_ob.beautify_in_progress = true;

    var source = js_beautify_ob.editor ? js_beautify_ob.editor.getValue() : $(js_beautify_ob).eq(0).val(),
        output,opts = {};
    opts.space_after_anon_function = true;

    if (looks_like_html(source)) {
        output = html_beautify(source, opts);
    } else {
        output = js_beautify(source, opts);
    }

    if (js_beautify_ob.editor) {
        js_beautify_ob.editor.setValue(output);
    } else {
        $(js_beautify_ob).eq(0).val(output);
    }

    js_beautify_ob.beautify_in_progress = false;
}