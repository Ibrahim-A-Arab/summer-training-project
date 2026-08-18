//lower section
const lower_hidden = $("#lower_hidden");

const task_name_field = $("#task_name_field");
const task_due_date_field = $("#task_due_date_field");
const task_severity_select = $("#task_severity_select");

const cancel_button = $("#cancel_button");
//upper
const filter_tasks = $("#filter_tasks");
const sort_tasks = $("#sort_tasks");
const search_tasks = $("#search");


//listeners

filter_tasks.on("change", function(){
    tasks_filter();
});

sort_tasks.on("change", function() {
    tasks_filter();
});

search_tasks.on("input", function() {
    tasks_filter();
});





let editing_id = null;


function save_tasks(tasks) {
    localStorage.setItem("tasks", JSON.stringify([...tasks]));
}

function get_tasks() {
    return new Map(JSON.parse(localStorage.getItem("tasks")) || []);
}

function show_lower_sec() {
    lower_hidden.removeClass("hidden");
}

function hide_lower_sec(){
    myForm.reset();
    lower_hidden.addClass("hidden");
}

function add_task(){
    if(task_name_field.val().trim() != ""
    && task_due_date_field.val() != ""
    && task_severity_select.val() != "none"){
        
        let tasks = get_tasks();

        const id = editing_id ?? Date.now();

        if(tasks.has(id)){
            const oldTask = tasks.get(id);

            tasks.set(id, {
                name: task_name_field.val().trim(),
                done: oldTask.done, // keep checkbox state
                date: task_due_date_field.val(),
                severity: task_severity_select.val()
            });
        }else{
            tasks.set(id, {
                name: task_name_field.val().trim(),
                done: false,
                date: task_due_date_field.val(),
                severity: task_severity_select.val()
            });
        }

        console.log(tasks);
        
        save_tasks(tasks);
        editing_id = null;
        hide_lower_sec();
        tasks_filter();
    }else{
        if(task_name_field.val().trim() == ""){
            alert("Enter a name please ...");
        }else if(task_due_date_field.val() == ""){
            alert("Enter a date please ...");
        }else if(task_severity_select.val() == "none"){
            alert("Select task's severity please ...");
        }else{
            alert("fill all fields");
        }
    }
}


function matchesDateSearch(date, search) {
    const dateParts = date.split("-");

    // Split search by -, /, . or spaces
    const searchParts = search
        .trim()
        .split(/[-/.\s]+/);
    
    for(part of searchParts){

        const index = dateParts.findIndex(datePart => Number(datePart) == part);

        if (index === -1) {
            return false;
        }

        dateParts.splice(index, 1);
    }

    return true;

}

function sortArr(sortedArr, sort){
    switch (sort) {
        case "date":
            sortedArr.sort(([idA, taskA], [idB, taskB]) => {
                return new Date(taskB.date) - new Date(taskA.date);
            });
            console.log("newest_first");
            break;
        case "date_rev":
            sortedArr.sort(([idA, taskA], [idB, taskB]) => {
                return new Date(taskA.date) - new Date(taskB.date);
            });
            console.log("oldest_first");
            break;
        case "az":
            sortedArr.sort(([idA, taskA], [idB, taskB]) => 
            taskA.name.localeCompare(taskB.name) );
            break;
        case "za":
            sortedArr.sort(([idA, taskA], [idB, taskB]) => 
            taskB.name.localeCompare(taskA.name) );
            break;
        default:
            break;
    }
}

function matchesSearch(task){
    const searchText = search_tasks.val().trim();

    return  searchText === "" ||
            task.name.toLowerCase().includes(searchText.toLowerCase()) ||
            matchesDateSearch(task.date, searchText);
}

function matchesFilter(filter, task){
    return  filter == "all" ||
        (filter == "active" && !task.done) ||
        (filter == "completed" && task.done);
}

function addRows(sortedArr, tasks){
    const tableBody = $(".table_body");
    tableBody.empty();

    sortedArr.forEach(([id,task]) => {
        
        const row = $(`
            <tr>
                <td>
                    <input type="checkbox" class="task_checkbox"
                        ${task.done ? "checked" : ""}>
                </td>
                <td>${task.name}</td>
                <td>${task.date}</td>
                <td>${task.severity}</td>
                <td>
                    <button class="edit_button sec_button" data-id="${id}">
                        Edit
                    </button>
                    <button class="delete_button sec_button">
                        Delete
                    </button>
                </td>
            </tr>
        `);

        tableBody.append(row);

        //checkbox eventlistener
        const checkbox = row.find(".task_checkbox");
        checkbox.on("change", function() {
            task.done = checkbox.prop("checked");
            save_tasks(tasks);
            tasks_filter();
        });

        //delete eventlistener
        const deleteBtn = row.find(".delete_button");
        deleteBtn.on("click", function() {
            const confirmed = confirm("Are you sure you want to delete this task?");
            if (!confirmed) {return;}
            tasks.delete(id);

            save_tasks(tasks);
            tasks_filter();
        });

        //edit eventlistener
        const editBtn = row.find(".edit_button");
        editBtn.on("click", function() {
            editing_id = id;

            show_lower_sec();

            task_name_field.val(task.name);
            task_due_date_field.val(task.date);
            task_severity_select.val(task.severity);

        });
    });
}

function updateProgress() {
    const tasks = $('.task_checkbox');
    const completed = $('.task_checkbox:checked');

    const percentage =  (tasks.length === 0)  ?   0   :    Math.round((completed.length / tasks.length) * 100);

    $('#progressBar').css("width", percentage+"%");
    $('#progressPercent').text(percentage+"%");
}

function tasks_filter() {
    const tasks = get_tasks();

    let filter = filter_tasks.val() ?? "all";
    let sort = sort_tasks.val() ?? "none";
    let search = search_tasks.val() ?? "";


    

    const filtered_tasks = [...tasks].filter(([id, task]) => {
        return matchesSearch(task) && matchesFilter(filter, task);
    });

    const sortedArr = filtered_tasks;
    console.log(sortedArr);

    sortArr(sortedArr, sort);

    console.log(sortedArr);

    addRows(sortedArr, tasks);

    updateProgress();

    
}




tasks_filter();