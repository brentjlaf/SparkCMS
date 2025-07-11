<!-- File: basic.image.php -->
<!-- Template: basic.image -->
<templateSetting caption="Image Settings" order="1">
    <dl class="sparkDialog _tpl-box">
        <dt>Source</dt>
        <dd>
            <input type="text" name="custom_src" id="custom_src" value="">
            <button type="button" class="btn btn-secondary" onclick="openMediaPicker('custom_src')">Browse</button>
        </dd>
    </dl>
    <dl class="sparkDialog _tpl-box">
        <dt>Alt Text</dt>
        <dd><input type="text" name="custom_alt" value=""></dd>
    </dl>
</templateSetting>
<img src="{custom_src}" alt="{custom_alt}" data-tpl-tooltip="Image"/>
