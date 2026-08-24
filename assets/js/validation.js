document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Forms
    |--------------------------------------------------------------------------
    */

    const forms =
        document.querySelectorAll('form');


    forms.forEach(function (form) {

        form.addEventListener(
            'submit',
            function (event) {

                /*
                |--------------------------------------------------------------------------
                | Required Fields
                |--------------------------------------------------------------------------
                */

                const requiredFields =
                    form.querySelectorAll(
                        '[required]'
                    );


                let valid =
                    true;


                requiredFields.forEach(
                    function (field) {

                        if (
                            field.disabled
                            || field.type === 'hidden'
                        ) {
                            return;
                        }


                        if (
                            field.value.trim() === ''
                        ) {

                            valid =
                                false;

                            field.classList.add(
                                'input-error'
                            );

                        } else {

                            field.classList.remove(
                                'input-error'
                            );
                        }

                    }
                );


                if (!valid) {

                    event.preventDefault();

                    const firstInvalid =
                        form.querySelector(
                            '.input-error'
                        );


                    if (firstInvalid) {

                        firstInvalid.focus();
                    }

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Password Confirmation
                |--------------------------------------------------------------------------
                */

                const newPassword =
                    form.querySelector(
                        '[name="new_password"]'
                    );


                const confirmPassword =
                    form.querySelector(
                        '[name="confirm_password"]'
                    );


                if (
                    newPassword
                    && confirmPassword
                    && newPassword.value !==
                       confirmPassword.value
                ) {

                    event.preventDefault();

                    alert(
                        'New passwords do not match.'
                    );

                    confirmPassword.focus();

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Amount
                |--------------------------------------------------------------------------
                */

                const amount =
                    form.querySelector(
                        '[name="amount"]'
                    );


                if (
                    amount
                    && amount.value !== ''
                ) {

                    const numericAmount =
                        Number(
                            amount.value
                        );


                    if (
                        !Number.isFinite(
                            numericAmount
                        )
                        || numericAmount <= 0
                    ) {

                        event.preventDefault();

                        alert(
                            'Please enter a valid amount greater than zero.'
                        );

                        amount.focus();
                    }
                }

            }
        );

    });

});