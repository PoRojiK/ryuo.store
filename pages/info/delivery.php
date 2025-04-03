<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@^2.0/dist/tailwind.min.css" rel="stylesheet">
    <title>Условия доставки - RYUO</title>
</head>
<body class="bg-gray-100">
    <?php include '../../components/navbar.php'; ?>

    <div class="min-h-screen flex flex-col">
        <main class="flex-1 py-10">
            <div class="container mx-auto px-4 md:px-0">
                <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">Условия доставки</h1>
                    <div class="px-4 md:px-0">
                        <nav class="uppercase text-gray-500 font-bold">
                            <a href="/" class="hover:underline">RYUO</a><span class="font-bold"> » </span><span class="font-bold">Информация</span>
                        </nav>
                        <h1 class="uppercase text-2xl font-bold text-gray-800">ДОСТАВКА</h1>
                    </div>

                <section class="mt-6">
                    <div class="bg-white p-8 rounded-lg shadow-md border border-gray-200">
                        <div class="space-y-6 text-gray-600">
                            <h2 class="text-2xl font-semibold text-gray-700 mb-6 border-b-2 border-gray-200 pb-2">Общие условия доставки</h2>
                            <p>Доставка платная.</p>
                            <p>Стоимость доставки высчитывается, исходя от вашего местоположения.</p>
                            <p>Стоимость и срок доставки можете уточнить на сайте при оформлении заказа.</p>

                            <h2 class="text-2xl font-semibold text-gray-700 mt-10 mb-6 border-b-2 border-gray-200 pb-2">Способы доставки</h2>
                            <p>На данный момент отправляем только <strong>Сдэком</strong> и <strong>Почтой России</strong>.</p>
                            <p><strong>Сдэк:</strong> до пункта выдачи или до двери курьером.</p>
                            <p><strong>Почта России:</strong> отправляем до почтового отделения, будьте внимательны при указании индекса в строке <strong>Почтовый индекс</strong> (если выбрали доставку почтой России).</p>

                            <h2 class="text-2xl font-semibold text-gray-700 mt-10 mb-6 border-b-2 border-gray-200 pb-2">Доставка по Санкт-Петербургу</h2>
                            <p>Доставку по Санкт-Петербургу можем организовать курьером по Вашему адресу. Для этого необходимо написать это в примечание.</p>

                            <h2 class="text-2xl font-semibold text-gray-700 mt-10 mb-6 border-b-2 border-gray-200 pb-2">Международная доставка</h2>
                            <p>В другие страны отправляем международной службой «Почта России». Стоимость вычисляется индивидуально, исходя от вашего местоположения.</p>

                            <h2 class="text-2xl font-semibold text-gray-700 mt-10 mb-6 border-b-2 border-gray-200 pb-2">Отслеживание посылки</h2>
                            <p>Можете отслеживать посылку по трек номеру, который придет вам на почту, когда заказ будет иметь статус «Выполнен».</p>
                        </div>
                    </div>
                </section>

            </div>
        </main>
        <?php include '../../components/footer.php'; ?>
    </div>
</body>
</html>