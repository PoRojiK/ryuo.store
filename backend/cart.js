document.addEventListener('DOMContentLoaded', () => {
    // Удаление товара
    document.querySelectorAll('.delete-item').forEach(button => {
        button.addEventListener('click', () => {
            console.log("Кнопка удаления нажата");
            const productId = button.dataset.productId;
            const size = button.dataset.size;
            if (!productId || !size) {
                console.error('Данные для удаления не указаны');
                return;
            }
            fetch('../backend/cart_remove.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, size: size })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.closest('.bg-white').remove(); // Удаляем карточку товара из DOM
                    showNotification(data.message, 'green'); // Показываем уведомление
                    updateTotalPrice(); // Обновляем общую сумму при удалении товара
                    updateTotalDiscount(); // Обновляем общую скидку при удалении товара
                } else {
                    showNotification(data.message || 'Произошла ошибка.', 'red');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                showNotification('Не удалось удалить товар из корзины.', 'red');
            });
        });
    });

    // Обработчик кнопки уменьшения количества
    document.querySelectorAll('.decrease-quantity').forEach(button => {
        button.addEventListener('click', () => {
            console.log("Кнопка уменьшения количества нажата");
            const productId = button.dataset.productId;
            const size = button.dataset.size;
            const container = button.parentElement;
            console.log("container:", container);
            if (container) {
                const quantityInput = container.querySelector('.quantity-input');
                const productTotalAmount = container.parentElement.querySelector('.product-total-amount');
                const productOriginalTotalAmount = container.parentElement.querySelector('.product-original-total-amount');
                console.log("quantityInput:", quantityInput);
                if (quantityInput) {
                    let quantity = parseInt(quantityInput.value);
                    if (quantity > 1) {
                        quantity--;
                        quantityInput.value = quantity;
                        console.log("Updating quantity to:", quantity);
                        updateQuantity(productId, size, quantity, productTotalAmount, productOriginalTotalAmount);
                        showNotification('Количество уменьшено до ' + quantity, 'blue');
                    }
                } else {
                    console.error('Не удалось найти элемент quantityInput');
                }
            } else {
                console.error('Не удалось найти контейнер с классом flex');
            }
        });
    });

    // Обработчик кнопки увеличения количества
    document.querySelectorAll('.increase-quantity').forEach(button => {
        button.addEventListener('click', () => {
            console.log("Кнопка увеличения количества нажата");
            const productId = button.dataset.productId;
            const size = button.dataset.size;
            const container = button.parentElement;
            console.log("container:", container);
            if (container) {
                const quantityInput = container.querySelector('.quantity-input');
                const productTotalAmount = container.parentElement.querySelector('.product-total-amount');
                const productOriginalTotalAmount = container.parentElement.querySelector('.product-original-total-amount');
                console.log("quantityInput:", quantityInput);
                if (quantityInput) {
                    let quantity = parseInt(quantityInput.value);
                    quantity++;
                    quantityInput.value = quantity;
                    console.log("Updating quantity to:", quantity);
                    updateQuantity(productId, size, quantity, productTotalAmount, productOriginalTotalAmount);
                    showNotification('Количество увеличено до ' + quantity, 'blue');
                } else {
                    console.error('Не удалось найти элемент quantityInput');
                }
            } else {
                console.error('Не удалось найти контейнер с классом flex');
            }
        });
    });

    // Функция для обновления количества товара
    function updateQuantity(productId, size, quantity, productTotalAmount, productOriginalTotalAmount) {
        console.log("Sending request to update quantity to:", quantity);
        fetch('../backend/cart_update_quantity.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, size: size, quantity: quantity })
        })
        .then(response => response.json())
        .then(data => {
            console.log("Ответ от сервера:", data);
            if (data.success) {
                updateTotalPrice(); // Обновляем общую сумму при изменении количества
                updateTotalDiscount(); // Обновляем общую скидку при изменении количества
                const unitPrice = parseFloat(data.unit_price);
                const discountedPrice = parseFloat(data.discounted_price);
                console.log("unitPrice:", unitPrice, "discountedPrice:", discountedPrice);
                const totalAmount = discountedPrice * quantity;
                const originalTotalAmount = unitPrice * quantity;
                productTotalAmount.textContent = formatPrice(totalAmount) + '₽'; // Обновляем сумму товара
                if (productOriginalTotalAmount) {
                    productOriginalTotalAmount.textContent = formatPrice(originalTotalAmount) + '₽'; // Обновляем исходную сумму товара без скидки
                }
                showNotification(data.message, 'green');
            } else {
                console.error('Ошибка обновления количества:', data.error);
                showNotification(data.message || 'Ошибка обновления количества.', 'red');
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            showNotification('Не удалось обновить количество товара.', 'red');
        });
    }

    // Обновление итоговой суммы
    function updateTotalPrice() {
        fetch('../backend/cart_get_total.php')
        .then(response => response.json())
        .then(data => {
            console.log("Данные от сервера:", data);
            if (data.success) {
                const totalPriceElement = document.getElementById('total-price');
                if (totalPriceElement) {
                    totalPriceElement.textContent = formatPrice(data.total_price) + '₽';
                } else {
                    console.error('Элемент с ID "total-price" не найден');
                }
            }
        })
        .catch(error => console.error('Ошибка при обновлении итоговой суммы:', error));
    }

    // Обновление общей суммы скидок
    function updateTotalDiscount() {
        fetch('../backend/cart_get_total.php')
        .then(response => response.json())
        .then(data => {
            console.log("Данные от сервера:", data);
            if (data.success) {
                const totalDiscountElement = document.getElementById('simple-discount');
                if (totalDiscountElement) {
                    totalDiscountElement.textContent = formatPrice(data.total_discount) + '₽';
                } else {
                    console.error('Элемент с ID "simple-discount" не найден');
                }
            }
        })
        .catch(error => console.error('Ошибка при обновлении общей суммы скидок:', error));
    }

    // Форматирование цены (удаление дробной части)
    function formatPrice(price) {
        return new Intl.NumberFormat('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(price);
    }

    // Обновляем общую сумму и скидку при загрузке страницы
    updateTotalPrice();
    updateTotalDiscount();
});
