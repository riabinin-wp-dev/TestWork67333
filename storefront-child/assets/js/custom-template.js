/**
 * Класс для управления поиском
 */
class CitySearch {
    constructor() {
        this.input = document.getElementById("search-city");
        this.results = document.getElementById("city-results");
        this.form = document.getElementById("search-city-form");

        if (this.input && this.results && this.form) {
            this.init();
        }
    }

    /**
     * Инициализация обработчиков событий
     */
    init() {
        this.form.addEventListener("submit", (event) => {
            event.preventDefault();
        });

        this.input.addEventListener(
            "input",
            this.debounce(() => this.searchCity(), 500)
        );
    }

    /**
     * Функция debounce для уменьшения частоты запросов
     * @param {Function} func
     * @param {number} delay
     * @returns {Function}
     */
    debounce(func, delay) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    /**
     * Отправляет AJAX-запрос на сервер
     */
    searchCity() {
        const searchValue = this.input.value.trim();
        if (searchValue.length < 2) return; // Минимальная длина поиска
    
        const formData = new FormData();
        formData.append("action", "search_cities");
        formData.append("search", searchValue);
    
        fetch(CitySearchData.ajaxurl, {
            method: "POST",
            body: formData,
        })
            .then((response) => response.text())
            .then((data) => {
                this.results.innerHTML = data;
            })
            .catch((error) => console.error("Ошибка:", error));
    }
}

// Запускаем поиск после загрузки страницы
document.addEventListener("DOMContentLoaded", () => new CitySearch());
