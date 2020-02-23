class Calendar {
    constructor(info, id){
        this.info = info;
        this.id = id;

        this.elements = new Array();
    }

    addElement(object){
        this.elements.push(object);
    }

    update(start, end) {
        this.elements.forEach(function (element) {
            element.update(start, end);
        });
    }

    remove() {
        this.elements.forEach(function (element) {
            element.remove();
        });
    }

}
