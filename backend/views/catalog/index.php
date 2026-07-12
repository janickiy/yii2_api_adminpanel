<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $title */

use yii\helpers\Url;

$this->title = $title;
$this->params['title'] = $title;
?>
<?php $this->beginBlock('css') ?>
<link rel="stylesheet" href="/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<?php $this->endBlock() ?>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="pb-3">
                            <a href="<?= Url::to(['/catalog/create']) ?>" class="btn btn-info btn-sm pull-left">
                                <span class="fa fa-plus"> &nbsp;</span> добавить
                            </a>
                        </div>
                        <table id="itemList" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>имя</th>
                                <th style="width: 10%">действия</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php $this->beginBlock('js') ?>
<script src="/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script>
$(function () {
    $("#itemList").DataTable({
        oLanguage: {
            sLengthMenu: "Отображено _MENU_ записей на страницу",
            sZeroRecords: "Ничего не найдено - извините",
            sInfo: "Показано с _START_ по _END_ из _TOTAL_ записей",
            sInfoEmpty: "Показано с 0 по 0 из 0 записей",
            sInfoFiltered: "(отфильтровано  _MAX_ всего записей)",
            oPaginate: {sFirst: "Первая", sLast: "Посл.", sNext: "След.", sPrevious: "Пред."},
            sSearch: ' <i class="fas fa-search" aria-hidden="true"></i>'
        },
        createdRow: function (row, data) { $(row).attr('id', 'rowid_' + data.id); },
        processing: true,
        responsive: true,
        autoWidth: true,
        serverSide: true,
        ajax: {url: "<?= Url::to(['/datatable/catalogs']) ?>"},
        columns: [
            {data: 'name', name: 'name'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ]
    });
    $('#itemList').on('click', 'a.deleteRow', function (event) {
        event.preventDefault();
        let rowid = $(this).data('id');
        let deleteUrl = $(this).attr('href');
        Swal.fire({
            title: "Вы уверены?",
            text: "Вы не сможете восстановить эту информацию!",
            showCancelButton: true,
            icon: 'warning',
            cancelButtonText: "Отмена",
            confirmButtonText: "Да, удалить!",
            reverseButtons: true,
            confirmButtonColor: "#DD6B55"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: deleteUrl,
                    type: "DELETE",
                    dataType: "json",
                    headers: {'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')},
                    success: function () {
                        $("#rowid_" + rowid).remove();
                        Swal.fire("Сделано!", "Данные успешно удалены!", 'success');
                    },
                    error: function (xhr) {
                        Swal.fire("Ошибка при удалении!", (xhr.responseJSON && xhr.responseJSON.message) || "Попробуйте еще раз", 'error');
                    }
                });
            }
        });
    });
});
</script>
<?php $this->endBlock() ?>
