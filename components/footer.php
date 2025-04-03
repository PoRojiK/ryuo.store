<!DOCTYPE html>
 <html lang="ru">
 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link href="https://cdn.jsdelivr.net/npm/tailwindcss@^2.0/dist/tailwind.min.css" rel="stylesheet">
     <style>
         html, body {
             height: 100%;
             margin: 0;
         }

         body {
             display: flex;
             flex-direction: column;
             min-height: 100vh;
         }

         main {
             flex: 1 0 auto;
         }

         .footer {
             flex-shrink: 0;
             background-color: black;
             color: white;
             padding: 1rem 20%;
             margin-top: auto;
             margin-top: 2rem;
             display: flex;
             flex-direction: column;
             font-size: 0.8rem;
         }

         .footer-grid {
             display: grid;
             grid-template-columns: 2fr 2fr 1fr; /* Изменено для распределения 2/5, 2/5, 1/5 */
             gap: 1rem;
         }

         .footer-title {
             font-size: 1rem;
             font-weight: bold;
             color: white;
         }

         .footer-bottom {
             display: flex;
             justify-content: space-between;
             align-items: center;
             margin-top: 1rem;
         }

         .footer-copyright {
             text-align: right;
         }

         .footer-social {
             display: flex;
             gap: 0.9rem;
         }

         .footer-social img {
             width: 30px;
             height: 30px;
         }

         .footer p{
             font-size: 0.8rem;
             color: #cbd5e0;
             text-align: justify; /* Добавлено для растягивания текста на ширину элемента */
         }

         .footer a {
             color: #cbd5e0;
             text-decoration: none;
             transition: color 0.3s ease, text-decoration 0.3s ease;
         }

         .footer a:hover {
             color: white;
             text-decoration: underline;
         }
     </style>
 </head>
 <body>
<main></main>

     <footer class="footer">
         <div class="footer-grid">
             <div>
                 <h3 class="footer-title">О нас</h3>
                 <p>Мы – российское творческое объединение с собственным производством, развивающееся в уличной моде, японской культуре и современном искусстве.  Наша миссия – создавать истории через продукты, вдохновляя и удивляя, придерживаясь высоких стандартов качества. Мы убеждены, что творчество не знает границ, и стремимся к новым вершинам в каждом начинании.</p>
             </div>
             <div>
                 <h3 class="footer-title">Информация</h3>
                 <a href="../../returns-exchanges">Обмен и возврат</a><br>
                 <a href="../../payments">Оплата</a><br>
                 <a href="../../delivery">Доставка</a><br>
                 <a href="../../privacy">Политика конфиденциальности персональных данных</a><br>
             </div>
             <div>
                 <h3 class="footer-title">Контакты</h3>
                 <p>Email: e-com@ryuo.store</p>
                 <p>Телефон: +7 924 710 4575</p>
                 <div class="footer-social">
                     <a href="https://t.me/ryuo_silvia">
                         <img src="/../images/icons/Telegram-negative.svg" alt="Telegram">
                     </a>
                     <a href="https://vk.com/poka_no_name">
                         <img src="/../images/icons/vk-negative.svg" alt="VK">
                     </a>
                 </div>
             </div>
         </div>
         <div class="footer-bottom">
             <img src="/../images/icons/logo.svg" alt="ryuo" class="footer-logo w-8 h-8">
             <div class="footer-copyright">
                 &copy; RYUO INC 2025.
             </div>
         </div>
     </footer>
 </body>
 </html>