/**
 * Perform a JSON request.
 *
 * This helper does not bypass authentication,
 * CSRF protection, or server-side validation.
 */

async function apiRequest(
    url,
    options = {}
) {

    const defaultOptions = {

        credentials:
            'same-origin',

        headers: {

            'Accept':
                'application/json'

        }

    };


    const requestOptions = {

        ...defaultOptions,

        ...options,

        headers: {

            ...defaultOptions.headers,

            ...(options.headers || {})

        }

    };


    const response =
        await fetch(
            url,
            requestOptions
        );


    /*
    |--------------------------------------------------------------------------
    | Parse Response
    |--------------------------------------------------------------------------
    */

    const contentType =
        response.headers.get(
            'content-type'
        ) || '';


    let data;


    if (
        contentType.includes(
            'application/json'
        )
    ) {

        data =
            await response.json();

    } else {

        data =
            await response.text();
    }


    /*
    |--------------------------------------------------------------------------
    | HTTP Error
    |--------------------------------------------------------------------------
    */

    if (!response.ok) {

        const message =
            typeof data === 'object'
            && data !== null
            && typeof data.message === 'string'

                ? data.message

                : 'Request failed.';


        throw new Error(
            message
        );
    }


    return data;
}