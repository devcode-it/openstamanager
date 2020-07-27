class LineChart extends Stat {
    constructor(calendar, file, data = {}, chart){
        super(calendar, file, data);

        this.chart = chart;
    }

    add(start, end, color = null) {
        var calendar_id = this.calendar.id;
        var chart = this.chart;

        var color = color ? color : this.getRandomColor();

        this.getData(start, end, function(response) {
            var data = JSON.parse(response);

            var label = data.label;

            var dataset = [];
            var labels = [];

            data.results.forEach(function (item) {
                dataset.push(item.result);

                var date = moment().month(item.month - 1).year(item.year);
                labels.push(date.format("MMMM YYYY"));
            });

            while (chart.data.labels.length < data.results.length) {
                chart.data.labels.push("");
            }

            chart.data.datasets.push({
                calendar_id: calendar_id,
                label: label,
                backgroundColor: color + '55',
                borderColor: color,
                data: dataset,
                labels: labels,
                fill: true,
            });

            chart.update();
        });
    }

    update(start, end) {
        var color = this.remove();

        this.add(start, end, color);
    }

    getRandomColor() {
        var letters = '0123456789ABCDEF';
        var color = '#';
        for (var i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }

    remove() {
        var chart = this.chart;
        var calendar_id = this.calendar.id;

        var max_length = 0;
        var dataset_index = undefined;

        chart.data.datasets.forEach(function (item, index) {
            if(item.calendar_id == calendar_id) {
                dataset_index = index;
            } else {
                if (item.data.length > max_length) {
                    max_length = item.data.length;
                }
            }
        });

        var color = null;
        if (dataset_index != undefined) {
            color = chart.data.datasets[dataset_index].backgroundColor;

            chart.data.datasets.splice(dataset_index, 1);
            chart.data.labels.splice(dataset_index, 1);
        }

        while (chart.data.labels.length > max_length) {
            chart.data.labels.pop("");
        }

        chart.update();

        return color;
    }
}
