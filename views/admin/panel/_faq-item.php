<?php
/**
 * FAQ Item Partial - Inline editable FAQ
 * @var object $faq
 */
?>
<div class="faq-item" data-uid="<?=htmlspecialchars($faq->uid)?>">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group mb-2">
                <label class="font-12 color-gray">Kategori</label>
                <input type="text" class="form-field-v2 w-100 faq-category" value="<?=htmlspecialchars($faq->category)?>" placeholder="F.eks. Generelt om WeePay">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group mb-2">
                <label class="font-12 color-gray">Sortering</label>
                <input type="number" class="form-field-v2 w-100 faq-sort" value="<?=$faq->sort_order?>" min="0">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group mb-2">
                <label class="font-12 color-gray">Status</label>
                <select class="form-select-v2 w-100 h-45px faq-active">
                    <option value="1" <?=$faq->is_active == 1 ? 'selected' : ''?>>Aktiv</option>
                    <option value="0" <?=$faq->is_active == 0 ? 'selected' : ''?>>Inaktiv</option>
                </select>
            </div>
        </div>
    </div>
    <div class="form-group mb-2">
        <label class="font-12 color-gray">Spørgsmål/Titel</label>
        <input type="text" class="form-field-v2 w-100 faq-title" value="<?=htmlspecialchars($faq->title)?>" placeholder="F.eks. Hvad er WeePay?">
    </div>
    <div class="form-group mb-3">
        <label class="font-12 color-gray">Svar</label>
        <div class="html-editor-container">
            <div class="html-editor-toolbar">
                <button type="button" onclick="execCmd('bold')" title="Fed"><i class="mdi mdi-format-bold"></i></button>
                <button type="button" onclick="execCmd('italic')" title="Kursiv"><i class="mdi mdi-format-italic"></i></button>
                <button type="button" onclick="execCmd('underline')" title="Understreget"><i class="mdi mdi-format-underline"></i></button>
                <button type="button" onclick="execLink(this)" title="Link"><i class="mdi mdi-link"></i></button>
                <button type="button" onclick="execCmd('insertUnorderedList')" title="Liste"><i class="mdi mdi-format-list-bulleted"></i></button>
            </div>
            <div class="faq-content-editor" contenteditable="true" oninput="syncContent(this)"><?=$faq->content?></div>
            <input type="hidden" class="faq-content" value="<?=htmlspecialchars($faq->content)?>">
        </div>
    </div>
    <div class="flex-row-end flex-align-center cg-15">
        <button type="button" class="btn-v2 danger-btn btn-sm" onclick="deleteFaq(this)">
            <i class="mdi mdi-delete"></i> Slet
        </button>
        <button type="button" class="btn-v2 action-btn btn-sm" onclick="saveFaq(this)">
            <i class="mdi mdi-content-save"></i> Gem
        </button>
    </div>
    <input type="hidden" class="faq-type" value="<?=htmlspecialchars($faq->type)?>">
</div>
