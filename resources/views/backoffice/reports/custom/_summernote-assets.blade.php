{{-- Shared Summernote CSS + JS for custom reports create/edit --}}

@push('styles')
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/summernote/summernote-lite.min.css') }}">
    <style>
        /* ── Editor frame ─────────────────────────────── */
        .note-editor.note-frame {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }

        /* ── Toolbar ──────────────────────────────────── */
        .note-editor .note-toolbar {
            background: linear-gradient(to bottom, #f8f9fb, #eef0f4);
            border-bottom: 1px solid #d1d5db;
            padding: 6px 8px;
            flex-wrap: wrap;
            gap: 2px;
        }
        .note-editor .note-toolbar .note-btn-group {
            margin-right: 4px;
        }
        .note-editor .note-toolbar .note-btn {
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 13px;
            border: 1px solid transparent;
            background: transparent;
            color: #374151;
            transition: all .15s;
        }
        .note-editor .note-toolbar .note-btn:hover {
            background: #fff;
            border-color: #c5c9d2;
            box-shadow: 0 1px 2px rgba(0,0,0,.06);
        }
        .note-editor .note-toolbar .note-btn.active {
            background: #e0e7ff;
            border-color: #818cf8;
            color: #4338ca;
        }
        .note-editor .note-toolbar .note-color-btn {
            padding: 2px 4px;
        }

        /* ── Editing area ─────────────────────────────── */
        .note-editor .note-editing-area .note-editable {
            padding: 20px 25px;
            min-height: 500px;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.8;
            color: #1f2937;
            background: #fff;
        }
        .note-editor .note-editing-area .note-editable:focus {
            outline: none;
        }

        /* ── Statusbar ────────────────────────────────── */
        .note-editor .note-statusbar {
            border-top: 1px solid #d1d5db;
            background: #f8f9fb;
        }
        .note-editor .note-statusbar .note-resizebar {
            border-top: none;
            padding: 3px 0;
        }

        /* ── Dropdown menus ───────────────────────────── */
        .note-editor .note-dropdown-menu {
            border-radius: 6px;
            border: 1px solid #d1d5db;
            box-shadow: 0 4px 12px rgba(0,0,0,.1);
            padding: 4px;
        }
        .note-editor .note-dropdown-item {
            border-radius: 4px;
            padding: 6px 10px;
        }
        .note-editor .note-dropdown-item:hover {
            background: #f3f4f6;
        }

        /* ── Modal dialogs ────────────────────────────── */
        .note-modal .modal-dialog {
            max-width: 550px;
        }
        .note-modal .note-modal-content {
            border-radius: 10px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,.15);
        }
        .note-modal .modal-header {
            border-bottom: 1px solid #e5e7eb;
            padding: 14px 20px;
        }
        .note-modal .modal-body {
            padding: 20px;
        }
        .note-modal .modal-footer {
            border-top: 1px solid #e5e7eb;
            padding: 12px 20px;
        }

        /* ── Tables inside editor ─────────────────────── */
        .note-editor .note-editable table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }
        .note-editor .note-editable table td,
        .note-editor .note-editable table th {
            border: 1px solid #d1d5db;
            padding: 10px 14px;
            vertical-align: top;
            min-width: 60px;
        }
        .note-editor .note-editable table th {
            background-color: #f1f5f9;
            font-weight: 600;
            color: #1e293b;
        }
        .note-editor .note-editable table tr:hover td {
            background-color: #f8fafc;
        }

        /* ── Table popover (resize/color handles) ─────── */
        .note-table .note-dimension-picker-mousecatcher {
            cursor: pointer;
        }

        /* ── Fullscreen mode ──────────────────────────── */
        .note-editor.note-frame.fullscreen {
            z-index: 10500;
        }
        .note-editor.note-frame.fullscreen .note-editable {
            background: #fff;
        }

        /* ── Custom table-tools popover ───────────────── */
        .note-popover .popover-content .note-table,
        .note-popover .popover-content {
            padding: 5px;
        }

        /* ── Row background color button ──────────────── */
        .btn-row-bg-color {
            position: relative;
        }
        .row-color-palette {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 10;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,.12);
            width: 200px;
        }
        .row-color-palette.show {
            display: block;
        }
        .row-color-palette .color-swatch {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            margin: 2px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all .15s;
        }
        .row-color-palette .color-swatch:hover {
            border-color: #6366f1;
            transform: scale(1.15);
        }
        .row-color-palette .palette-label {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 4px;
            display: block;
        }

        /* ── Dark mode overrides ──────────────────────── */
        [data-bs-theme=dark] .note-editor.note-frame {
            border-color: #3a3f51;
            box-shadow: 0 1px 3px rgba(0,0,0,.2);
        }
        [data-bs-theme=dark] .note-editor .note-toolbar {
            background: linear-gradient(to bottom, #2a2e3e, #252836);
            border-color: #3a3f51;
        }
        [data-bs-theme=dark] .note-editor .note-toolbar .note-btn {
            color: #c9d1d9;
        }
        [data-bs-theme=dark] .note-editor .note-toolbar .note-btn:hover {
            background: #363b4e;
            border-color: #4a5068;
        }
        [data-bs-theme=dark] .note-editor .note-toolbar .note-btn.active {
            background: #312e81;
            border-color: #6366f1;
            color: #a5b4fc;
        }
        [data-bs-theme=dark] .note-editor .note-editing-area .note-editable {
            background: #1e2235;
            color: #c9d1d9;
        }
        [data-bs-theme=dark] .note-editor .note-editable table {
            border-color: #3a3f51;
        }
        [data-bs-theme=dark] .note-editor .note-editable table td,
        [data-bs-theme=dark] .note-editor .note-editable table th {
            border-color: #3a3f51;
        }
        [data-bs-theme=dark] .note-editor .note-editable table th {
            background-color: #2a2e3e;
            color: #e2e8f0;
        }
        [data-bs-theme=dark] .note-editor .note-editable table tr:hover td {
            background-color: #262a3a;
        }
        [data-bs-theme=dark] .note-editor .note-statusbar {
            background: #252836;
            border-color: #3a3f51;
        }
        [data-bs-theme=dark] .note-editor .note-dropdown-menu {
            background: #2a2e3e;
            border-color: #3a3f51;
        }
        [data-bs-theme=dark] .note-editor .note-dropdown-item:hover {
            background: #363b4e;
        }
        [data-bs-theme=dark] .row-color-palette {
            background: #2a2e3e;
            border-color: #3a3f51;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ URL::asset('build/plugins/summernote/summernote-lite.min.js') }}"></script>
    <script>
    $(document).ready(function() {

        // ── Custom button: Row Background Color ──────────
        var RowBgColorButton = function(context) {
            var ui = $.summernote.ui;
            var colors = [
                { label: 'Aucune', colors: ['transparent'] },
                { label: 'Basiques', colors: ['#ffffff','#f8f9fa','#e9ecef','#dee2e6','#ced4da','#adb5bd','#6c757d','#495057','#343a40','#212529'] },
                { label: 'Couleurs', colors: ['#fff3cd','#fef3c7','#fde68a','#d1fae5','#a7f3d0','#6ee7b7','#dbeafe','#bfdbfe','#93c5fd','#e0e7ff','#c7d2fe','#a5b4fc','#fce7f3','#fbcfe8','#f9a8d4','#fee2e2','#fecaca','#fca5a5','#ffedd5','#fed7aa','#fdba74'] },
            ];

            var button = ui.button({
                contents: '<i class="note-icon-magic"></i> <span style="font-size:11px">Fond ligne</span>',
                tooltip: 'Couleur de fond de la ligne',
                className: 'btn-row-bg-color',
                click: function() {
                    var $palette = $(this).find('.row-color-palette');
                    if ($palette.length) {
                        $palette.toggleClass('show');
                        return;
                    }

                    var html = '<div class="row-color-palette show">';
                    colors.forEach(function(group) {
                        html += '<span class="palette-label">' + group.label + '</span>';
                        group.colors.forEach(function(c) {
                            var style = c === 'transparent'
                                ? 'background:repeating-conic-gradient(#ccc 0% 25%,#fff 0% 50%) 50%/12px 12px'
                                : 'background:' + c;
                            html += '<span class="color-swatch" data-color="' + c + '" style="' + style + '" title="' + c + '"></span>';
                        });
                        html += '<br>';
                    });
                    html += '</div>';

                    $(this).css('position', 'relative').append(html);

                    $(this).find('.color-swatch').on('click', function(e) {
                        e.stopPropagation();
                        var color = $(this).data('color');

                        // Find the current row in the editor
                        var node = context.invoke('restoreTarget') || window.getSelection().anchorNode;
                        var $row = $(node).closest('tr');
                        if (!$row.length && node) {
                            $row = $(node).parents('tr').first();
                        }
                        if ($row.length) {
                            if (color === 'transparent') {
                                $row.css('background-color', '');
                                $row.find('td, th').css('background-color', '');
                            } else {
                                $row.css('background-color', color);
                                $row.find('td, th').css('background-color', color);
                            }
                        }
                        $(this).closest('.row-color-palette').removeClass('show');
                    });
                }
            });

            return button.render();
        };

        // ── Custom button: Cell Background Color ─────────
        var CellBgColorButton = function(context) {
            var ui = $.summernote.ui;
            var cellColors = ['transparent','#fff3cd','#d1fae5','#dbeafe','#fce7f3','#fee2e2','#ffedd5','#e0e7ff','#f8f9fa','#e9ecef','#fef3c7','#a7f3d0','#93c5fd','#f9a8d4','#fca5a5','#fdba74','#c7d2fe'];

            var button = ui.button({
                contents: '<i class="note-icon-pencil"></i> <span style="font-size:11px">Fond cellule</span>',
                tooltip: 'Couleur de fond de la cellule',
                click: function() {
                    var $palette = $(this).find('.row-color-palette');
                    if ($palette.length) {
                        $palette.toggleClass('show');
                        return;
                    }

                    var html = '<div class="row-color-palette show">';
                    cellColors.forEach(function(c) {
                        var style = c === 'transparent'
                            ? 'background:repeating-conic-gradient(#ccc 0% 25%,#fff 0% 50%) 50%/12px 12px'
                            : 'background:' + c;
                        html += '<span class="color-swatch" data-color="' + c + '" style="' + style + '" title="' + c + '"></span>';
                    });
                    html += '</div>';

                    $(this).css('position', 'relative').append(html);

                    $(this).find('.color-swatch').on('click', function(e) {
                        e.stopPropagation();
                        var color = $(this).data('color');
                        var node = window.getSelection().anchorNode;
                        var $cell = $(node).closest('td, th');
                        if (!$cell.length && node) {
                            $cell = $(node).parents('td, th').first();
                        }
                        if ($cell.length) {
                            $cell.css('background-color', color === 'transparent' ? '' : color);
                        }
                        $(this).closest('.row-color-palette').removeClass('show');
                    });
                }
            });

            return button.render();
        };

        // ── Custom button: Insert Styled Table ───────────
        var StyledTableButton = function(context) {
            var ui = $.summernote.ui;

            var button = ui.button({
                contents: '<i class="note-icon-table"></i> <span style="font-size:11px">Tableau</span>',
                tooltip: 'Insérer un tableau avec en-tête',
                click: function() {
                    var rows = prompt('Nombre de lignes (sans en-tête) :', '3');
                    var cols = prompt('Nombre de colonnes :', '3');
                    if (!rows || !cols) return;
                    rows = parseInt(rows) || 3;
                    cols = parseInt(cols) || 3;

                    var table = '<table style="width:100%;border-collapse:collapse;border:1px solid #d1d5db;margin:12px 0">';
                    // Header
                    table += '<thead><tr>';
                    for (var c = 0; c < cols; c++) {
                        table += '<th style="border:1px solid #d1d5db;padding:10px 14px;background-color:#f1f5f9;font-weight:600;color:#1e293b">En-tête ' + (c + 1) + '</th>';
                    }
                    table += '</tr></thead>';
                    // Body
                    table += '<tbody>';
                    for (var r = 0; r < rows; r++) {
                        table += '<tr>';
                        for (var c2 = 0; c2 < cols; c2++) {
                            table += '<td style="border:1px solid #d1d5db;padding:10px 14px;vertical-align:top">&nbsp;</td>';
                        }
                        table += '</tr>';
                    }
                    table += '</tbody></table><p><br></p>';

                    context.invoke('editor.pasteHTML', table);
                }
            });

            return button.render();
        };

        // ── Custom button: Add Table Row ─────────────────
        var AddRowButton = function(context) {
            var ui = $.summernote.ui;
            var button = ui.button({
                contents: '<i class="note-icon-row-below"></i>',
                tooltip: 'Ajouter une ligne',
                click: function() {
                    var node = window.getSelection().anchorNode;
                    var $row = $(node).closest('tr');
                    if (!$row.length) $row = $(node).parents('tr').first();
                    if ($row.length) {
                        var cols = $row.find('td, th').length;
                        var newRow = '<tr>';
                        for (var i = 0; i < cols; i++) {
                            newRow += '<td style="border:1px solid #d1d5db;padding:10px 14px;vertical-align:top">&nbsp;</td>';
                        }
                        newRow += '</tr>';
                        $row.after(newRow);
                    }
                }
            });
            return button.render();
        };

        // ── Custom button: Add Table Column ──────────────
        var AddColButton = function(context) {
            var ui = $.summernote.ui;
            var button = ui.button({
                contents: '<i class="note-icon-col-after"></i>',
                tooltip: 'Ajouter une colonne',
                click: function() {
                    var node = window.getSelection().anchorNode;
                    var $cell = $(node).closest('td, th');
                    if (!$cell.length) $cell = $(node).parents('td, th').first();
                    if ($cell.length) {
                        var index = $cell.index();
                        $cell.closest('table').find('tr').each(function() {
                            var cellTag = $(this).find('th').length && index < $(this).find('th').length ? 'th' : 'td';
                            var style = cellTag === 'th'
                                ? 'border:1px solid #d1d5db;padding:10px 14px;background-color:#f1f5f9;font-weight:600;color:#1e293b'
                                : 'border:1px solid #d1d5db;padding:10px 14px;vertical-align:top';
                            $(this).find('td, th').eq(index).after('<' + cellTag + ' style="' + style + '">&nbsp;</' + cellTag + '>');
                        });
                    }
                }
            });
            return button.render();
        };

        // ── Custom button: Delete Table Row ──────────────
        var DeleteRowButton = function(context) {
            var ui = $.summernote.ui;
            var button = ui.button({
                contents: '<i class="note-icon-row-remove"></i>',
                tooltip: 'Supprimer la ligne',
                click: function() {
                    var node = window.getSelection().anchorNode;
                    var $row = $(node).closest('tr');
                    if (!$row.length) $row = $(node).parents('tr').first();
                    if ($row.length) $row.remove();
                }
            });
            return button.render();
        };

        // ── Custom button: Delete Table Column ───────────
        var DeleteColButton = function(context) {
            var ui = $.summernote.ui;
            var button = ui.button({
                contents: '<i class="note-icon-col-remove"></i>',
                tooltip: 'Supprimer la colonne',
                click: function() {
                    var node = window.getSelection().anchorNode;
                    var $cell = $(node).closest('td, th');
                    if (!$cell.length) $cell = $(node).parents('td, th').first();
                    if ($cell.length) {
                        var index = $cell.index();
                        $cell.closest('table').find('tr').each(function() {
                            $(this).find('td, th').eq(index).remove();
                        });
                    }
                }
            });
            return button.render();
        };

        // ── Custom button: Merge/Split Cells ─────────────
        var MergeCellsButton = function(context) {
            var ui = $.summernote.ui;
            var button = ui.button({
                contents: '<i class="note-icon-align-justify"></i> <span style="font-size:11px">Fusionner</span>',
                tooltip: 'Fusionner les cellules (colspan +1)',
                click: function() {
                    var node = window.getSelection().anchorNode;
                    var $cell = $(node).closest('td, th');
                    if (!$cell.length) $cell = $(node).parents('td, th').first();
                    if ($cell.length) {
                        var colspan = parseInt($cell.attr('colspan') || 1);
                        var $next = $cell.next('td, th');
                        if ($next.length) {
                            $cell.attr('colspan', colspan + 1);
                            $cell.html($cell.html() + ' ' + $next.html());
                            $next.remove();
                        }
                    }
                }
            });
            return button.render();
        };

        // ── Custom button: Center Image ──────────────────
        var ImageCenterButton = function(context) {
            var ui = $.summernote.ui;
            var button = ui.button({
                contents: '<i class="note-icon-align-center"></i>',
                tooltip: 'Centrer l\'image',
                click: function() {
                    var $img = $(context.invoke('restoreTarget'));
                    if ($img.is('img')) {
                        // Remove any float
                        $img.css({ 'float': '', 'margin-left': '', 'margin-right': '' });
                        // Wrap in centered div or apply block center
                        var $parent = $img.parent();
                        if ($parent.is('p, div, span')) {
                            $parent.css('text-align', 'center');
                        } else {
                            $img.wrap('<p style="text-align:center"></p>');
                        }
                        $img.css({ 'display': 'inline-block', 'margin': '0 auto' });
                    }
                }
            });
            return button.render();
        };

        // ── Close palettes on outside click ──────────────
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.btn-row-bg-color, .row-color-palette').length) {
                $('.row-color-palette').removeClass('show');
            }
        });

        // ── Initialize Summernote ────────────────────────
        $('#summernote').summernote({
            height: 550,
            placeholder: 'Commencez à rédiger votre rapport ici...',
            tabsize: 2,
            dialogsInBody: true,
            disableDragAndDrop: false,

            toolbar: [
                ['undo',        ['undo', 'redo']],
                ['style',       ['style']],
                ['font',        ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
                ['fontname',    ['fontname']],
                ['fontsize',    ['fontsize']],
                ['color',       ['forecolor', 'backcolor']],
                ['para',        ['ul', 'ol', 'paragraph']],
                ['height',      ['height']],
                ['styledTable', ['styledTable']],
                ['tableEdit',   ['addRow', 'addCol', 'deleteRow', 'deleteCol', 'mergeCells']],
                ['tableBg',     ['rowBgColor', 'cellBgColor']],
                ['insert',      ['link', 'picture', 'video', 'hr']],
                ['view',        ['fullscreen', 'codeview', 'help']],
            ],

            buttons: {
                styledTable: StyledTableButton,
                rowBgColor: RowBgColorButton,
                cellBgColor: CellBgColorButton,
                addRow: AddRowButton,
                addCol: AddColButton,
                deleteRow: DeleteRowButton,
                deleteCol: DeleteColButton,
                mergeCells: MergeCellsButton,
                imageCenter: ImageCenterButton,
            },

            fontNames: [
                'Arial', 'Arial Black', 'Calibri', 'Cambria', 'Comic Sans MS', 'Courier New',
                'Georgia', 'Helvetica', 'Impact', 'Lucida Console', 'Lucida Sans',
                'Palatino Linotype', 'Segoe UI', 'Tahoma', 'Times New Roman',
                'Trebuchet MS', 'Verdana'
            ],

            fontSizes: ['8','9','10','11','12','13','14','16','18','20','24','28','32','36','42','48','56','64','72','96'],

            styleTags: [
                'p',
                { title: 'Titre 1', tag: 'h1', className: '', value: 'h1' },
                { title: 'Titre 2', tag: 'h2', className: '', value: 'h2' },
                { title: 'Titre 3', tag: 'h3', className: '', value: 'h3' },
                { title: 'Titre 4', tag: 'h4', className: '', value: 'h4' },
                { title: 'Titre 5', tag: 'h5', className: '', value: 'h5' },
                { title: 'Titre 6', tag: 'h6', className: '', value: 'h6' },
                'blockquote', 'pre'
            ],

            popover: {
                image: [
                    ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter', 'resizeNone']],
                    ['float', ['floatLeft', 'floatRight', 'floatNone']],
                    ['align', ['imageCenter']],
                    ['remove', ['removeMedia']]
                ],
                link: [
                    ['link', ['linkDialogShow', 'unlink']]
                ],
                table: [
                    ['add', ['addRowDown', 'addRowUp', 'addColLeft', 'addColRight']],
                    ['delete', ['deleteRow', 'deleteCol', 'deleteTable']],
                ],
            },

            callbacks: {
                onImageUpload: function(files) {
                    for (var i = 0; i < files.length; i++) {
                        (function(file) {
                            var reader = new FileReader();
                            reader.onloadend = function() {
                                var img = $('<img>')
                                    .attr('src', this.result)
                                    .css({ 'max-width': '100%', 'height': 'auto', 'border-radius': '4px' });
                                $('#summernote').summernote('insertNode', img[0]);
                            };
                            reader.readAsDataURL(file);
                        })(files[i]);
                    }
                },
                onInit: function() {
                    // Apply default table styles when pasting HTML with tables
                    var $editable = $(this).siblings('.note-editor').find('.note-editable');
                    $editable.on('DOMNodeInserted', function(e) {
                        if (e.target.tagName === 'TABLE') {
                            $(e.target).css({
                                'width': '100%',
                                'border-collapse': 'collapse',
                                'border': '1px solid #d1d5db',
                                'margin': '12px 0'
                            });
                            $(e.target).find('td, th').css({
                                'border': '1px solid #d1d5db',
                                'padding': '10px 14px',
                                'vertical-align': 'top'
                            });
                            $(e.target).find('th').css({
                                'background-color': '#f1f5f9',
                                'font-weight': '600',
                                'color': '#1e293b'
                            });
                        }
                    });
                }
            }
        });
    });
    </script>
@endpush
