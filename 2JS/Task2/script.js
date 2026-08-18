const screen = $("#screen");

const buttons = $(".buttons button");

//Event listener for the calculator main buttons
buttons.on("click", function () {
    const value = $(this).data("value");
        switch($(this).data("value")){
            case "=":
                calculate();
                break;
            case "C":
                screen.text("");
                break;
            case "⌫":
                if(screen.text().length>0){
                    screen.text(screen.text().slice(0,-1));
                }
                break;
            default:
                screen.text(screen.text() + value);
            break;
        } 
})

//the function that translates infix to postfix and then calls the evaluation function 
function calculate() {
    const exp = screen.text();

    const priority = {
        "+": 1,
        "-": 1,
        "x": 2,
        "÷": 2,
        "%": 2
    };

    const stack = new Stack();
    const expression = [];
    let number = "";

    if (exp.length === 0) {//length>0
        return;
    }

    if (exp[0] in priority && exp[0] !== "-" && exp[0] !== "+") {//operator not 1st
        screen.text("Exception, operator is 1st");
        return;
    }

    if (exp[exp.length - 1] in priority) {//operator not last
        screen.text("Exception, operator is last");
        return;
    }

    for (let i = 0; i < exp.length; i++) {
        const char = exp[i];

        if (
            (char === "-" ||char === "+") &&
            (
                i === 0 ||
                exp[i - 1] in priority ||
                exp[i - 1] === "("
            )
        ) {
            if(char === "-"){
                if(number == "-"){
                    number = ""
                }else{
                    number = "-";
                }
                
            }

            continue;
        }


        // numbers and decimals
        if ((char >= "0" && char <= "9") || char === ".") {

            if (i > 0 && exp[i - 1] === ")") {
                screen.text("Exception, missing operator");
                return;
            }

            if (char === "." && number.includes(".")) {
                screen.text("Exception, invalid decimal");
                return;
            }

            if (char === "." && number === "") {
                number = "0.";
            }else if (char === "." && number === "-") {
                number = "-0.";
            }

            else {
                number += char;
            }

            continue;
        }

        if (char === "(" &&
            i > 0 &&
            ((exp[i - 1] >= "0" && exp[i - 1] <= "9") ||
            exp[i - 1] === ")" ||
            exp[i - 1] === "."
            )
        )
        {
            screen.text("Exception, missing operator");
            return;
        }

        if (number !== "") {
            expression.push(number);
            number = "";
        }

        if (char === "(") {
            if(exp[i+1]===")"){
                screen.text("Exception Empyt parentheses");
                return;
            }
            stack.push(char);
        }

        else if (char === ")") {
            while (!stack.isEmpty() && stack.peek() !== "(") {
                expression.push(stack.pop());
            }

            if (stack.isEmpty()) {
                screen.text("Exception, missing (");
                return;
            }

            stack.pop();
        }

        else if (char in priority) {
            if ((exp[i - 1] in priority && exp[i - 1] !== "+" && exp[i - 1] !== "-") || exp[i - 1] === "(") {
                screen.text("Exception, two consecutive operators ");
                return;
            }
            while (
                !stack.isEmpty() &&
                stack.peek() !== "(" &&
                priority[stack.peek()] >= priority[char]
            ) {
                expression.push(stack.pop());
            }

            stack.push(char);
        }
    }

    if (number !== "") {
        expression.push(number);
    }

    while (!stack.isEmpty()) {
        if (stack.peek() === "(") {
            screen.text("Exception, missing )");
            return;
        }
        expression.push(stack.pop());
    }

    console.log(expression);
    console.log(stack);

    const result = evaluatePostfix(expression);

    screen.text(result);

}
//the evaluation function that calculates the result of the postfix 
function evaluatePostfix(expression) {
    const stack = new Stack();

    for (let i = 0; i < expression.length; i++) {
        const item = expression[i];

        if (!isNaN(item)) {
            stack.push(Number(item));
        }

        

        else {
            if (stack.size() < 2) {
                return "Exception, invalid expression";
            }
            const b = stack.pop();
            const a = stack.pop();

            let result;

            switch (item) {
                case "+":
                    result = a + b;
                    break;

                case "-":
                    result = a - b;
                    break;

                case "x":
                    result = a * b;
                    break;

                case "÷":
                    if(b==0){
                        return "Arithmatic Exception, div by zero";
                    }
                    result = a / b;
                    break;

                case "%":
                    result = a % b;
                    break;
            }
            // stack.push(Math.round(result*10e10)/10e10);
            // stack.push(Number(result).toFixed(12))//displays Zeros on the right
            // stack.push(Number(result).toFixed(2))//destroys small numbers
            stack.push(result);//displays 0.00000000000000000000001
        }
    }

    if (stack.size() !== 1) {
        return "Exception, invalid expression";
    }

    return stack.pop();
}





//keyboard listener
$(document).on("keydown", function (event) {
    const key = event.key;

    if (/^[0-9.+%-]$/.test(key)) {
        screen.text(screen.text() + key);
        return;
    }

    else if (key === "*" || key === "x" || key === "X") {
        screen.text(screen.text() + "x");
    }

    else if (key === "(" || key === ")") {
        screen.text(screen.text() + key);
    }

    else if (key === "/") {
        event.preventDefault();
        screen.text(screen.text() + "÷");
    }

    else if (key === "Enter" || key === "=") {
        event.preventDefault();
        calculate();
    }

    else if (key === "Backspace") {
        screen.text(screen.text().slice(0, -1));
    }

    else if (key === "Escape") {
        screen.text("");
    }
});