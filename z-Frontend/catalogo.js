const productos = [
    { id: 1, nombre: "Podcast", img: "https://images.unsplash.com/photo-1581092335075-922b6f894a2c?q=80&w=1000" },
    { id: 2, nombre: "Audiolibro", img: "https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?q=80&w=1000" },
    { id: 3, nombre: "Película", img: "https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=1000" }
];

let index = 0;

function mostrarCarrusel() {
    const img = document.querySelector(".carousel-image");
    img.src = productos[index].img;

    index = (index + 1) % productos.length;
}
setInterval(mostrarCarrusel, 3000);

document.querySelector("#addCart").addEventListener("click", () => {
    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

    carrito.push(productos[index === 0 ? productos.length - 1 : index - 1]);

    localStorage.setItem("carrito", JSON.stringify(carrito));

    const btn = document.querySelector("#addCart");
    btn.classList.add("zoom");
    setTimeout(() => btn.classList.remove("zoom"), 300);
});

document.querySelector("#goCart").addEventListener("click", () => {
    window.location.href = "carrito.html";
});
