let current = new Date();

const dayNames = ["Mo", "Tu", "We", "Th", "Fr", "Sa", "Su"];

function formatMonth(date) {
  return date.toISOString().slice(0, 7);
}

function getColor(price, min, max) {
  const ratio = (price - min) / (max - min || 1);
  if (ratio < 0.33) return "low";
  if (ratio < 0.66) return "mid";
  return "high";
}

async function loadCalendar() {
  const monthStr = formatMonth(current);
  const res = await fetch(`${PRICE_API.url}?month=${monthStr}`);
  const prices = await res.json();

  const values = Object.values(prices);
  const min = Math.min(...values);
  const max = Math.max(...values);

  const grid = document.getElementById("calendarGrid");
  grid.innerHTML = "";

  dayNames.forEach(d => {
    grid.innerHTML += `<div class="day-name">${d}</div>`;
  });

  const firstDay = new Date(current.getFullYear(), current.getMonth(), 1);
  const lastDay = new Date(current.getFullYear(), current.getMonth() + 1, 0);

  let start = firstDay.getDay();
  if (start === 0) start = 7;

  for (let i = 1; i < start; i++) grid.innerHTML += "<div></div>";

  for (let d = 1; d <= lastDay.getDate(); d++) {
    const dateStr = `${monthStr}-${String(d).padStart(2, '0')}`;
    const price = prices[dateStr];

    let priceHTML = "";
    if (price) {
      const color = getColor(price, min, max);
      priceHTML = `<div class="price ${color}">${price.toLocaleString()}₫</div>`;
    }

    grid.innerHTML += `
      <div class="day">
        <div class="day-number">${d}</div>
        ${priceHTML}
      </div>`;
  }

  document.getElementById("calendarTitle").innerText =
    current.toLocaleString("default", { month: "long", year: "numeric" });
}

document.addEventListener("DOMContentLoaded", () => {
  loadCalendar();

  document.getElementById("nextMonth").onclick = () => {
    current.setMonth(current.getMonth() + 1);
    loadCalendar();
  };

  document.getElementById("prevMonth").onclick = () => {
    current.setMonth(current.getMonth() - 1);
    loadCalendar();
  };
});