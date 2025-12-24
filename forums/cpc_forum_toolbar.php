<?php
function cpc_get_bbcode_toolbar($textarea_id = 'cpc_forum_post_edit_textarea', $textarea_name = 'cpc_forum_post_edit_textarea', $content = '') {
    return '
    <div class="cpc_bbcode_toolbar">
        <button type="button" data-tag="b"><b>B</b></button>
        <button type="button" data-tag="i"><i>I</i></button>
        <button type="button" data-tag="u"><u>U</u></button>
        <button type="button" data-tag="quote">Zitat</button>
        <button type="button" data-tag="code">Code</button>
        <button type="button" data-tag="url">Link</button>
        <button type="button" data-tag="img">Bild</button>
    </div>
    <textarea id="'.$textarea_id.'" name="'.$textarea_name.'">'.htmlspecialchars($content).'</textarea>
    ';
}