(function ($, yii, Swal) {
    'use strict';

    yii.confirm = function (message, ok, cancel) {
        Swal.fire({
            title: 'Подтвердите удаление',
            text: String(message),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Удалить',
            cancelButtonText: 'Отмена',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            focusCancel: true,
            reverseButtons: true,
        }).then(function (result) {
            if (result.isConfirmed) {
                if ($.isFunction(ok)) {
                    ok();
                }

                return;
            }

            if ($.isFunction(cancel)) {
                cancel();
            }
        });
    };
})(window.jQuery, window.yii, window.Swal);
