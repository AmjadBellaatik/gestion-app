<style>

    @page {

        margin-top: 125px;
        margin-bottom: 110px;

    }

    body {

        font-family: DejaVu Sans, sans-serif;

        font-size: 12px;

        color: #000;

    }

    .rtl {

        direction: rtl;
        text-align: right;

    }

    .ltr {

        direction: ltr;
        text-align: left;

    }

    .header {

        position: fixed;

        top: -100px;
        left: 0;
        right: 0;

        height: 95px;

    }

    .footer {

        position: fixed;

        bottom: -85px;
        left: 0;
        right: 0;

        height: 75px;

        font-size: 10px;

    }

    .page-number:after {

        content: counter(page);

    }

    .watermark {

        position: fixed;

        top: 35%;

        width: 100%;

        text-align: center;

        opacity: 0.08;

        font-size: 90px;

        transform: rotate(-25deg);

    }

    .qr-code {

        position: absolute;

        top: 30px;
        right: 30px;

        width: 90px;

    }

</style>
