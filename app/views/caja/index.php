<?php if (isset($_SESSION['mensaje'])): ?>
    <div class="alert alert-<?php echo $_SESSION['mensaje_tipo'] ?? 'info'; ?> alert-dismissible fade show mt-3" role="alert">
        <?php echo $_SESSION['mensaje']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
<?php endif; ?>

<div class="container-fluid">
    <h1 class="mt-4 text-primary"><i class="fas fa-calculator"></i> Arqueo de Caja</h1>

    <?php if (!$turno_abierto): ?>
        <div class="card shadow-sm mt-4 ">
            <div class="card-header">
                <h4>Iniciar Turno</h4>
            </div>
            
            <div class="card-body">
                <p>No tienes un turno de caja activo. Ingresa el monto inicial para comenzar.</p>
                <form action="<?php echo BASE_URL; ?>caja/abrir" method="POST">
                    <div class="mb-3">
                        <label for="monto_inicial" class="form-label">Monto Inicial (Fondo de caja)</label>
                        <div class="input-group">
                            <span class="input-group-text"><?php echo getMoneda(); ?></span>
                            <input type="number" step="0.01" class="form-control" name="monto_inicial" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-play-circle"></i> Abrir Caja</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h4>Turno Activo</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Cajero:</strong> <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></p>
                        <p><strong>Fecha de Apertura:</strong> <?php echo date('d/m/Y H:i:s', strtotime($turno_abierto['fecha_apertura'])); ?></p>
                        <p><strong>Monto Inicial:</strong> <?php echo getMoneda(); ?><?php echo number_format($turno_abierto['monto_inicial'], 2); ?></p>
                        <hr>
                        <h5>Cerrar Turno</h5>
                        <form action="<?php echo BASE_URL; ?>caja/cerrar" method="POST">
                            <input type="hidden" name="id_turno" value="<?php echo $turno_abierto['id_turno']; ?>">
                            <div class="mb-3">
                                <label for="monto_final_real" class="form-label">Monto Final Contado</label>
                                <div class="input-group">
                                    <span class="input-group-text"><?php echo getMoneda(); ?></span>
                                    <input type="number" step="0.01" class="form-control" name="monto_final_real" id="monto_final_real" required
                                        value="<?php
                                                if (isset($_SESSION['monto_final_real'])) {
                                                    echo htmlspecialchars($_SESSION['monto_final_real']);
                                                    unset($_SESSION['monto_final_real']);
                                                } else {
                                                    echo number_format($turno_abierto['monto_inicial'], 2);
                                                }
                                                ?>">
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger"><i class="fas fa-stop-circle"></i> Cerrar Caja</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            
            <!-- Calculadora científica en el costado derecho -->
            <div class="col-md-4">
                <div class="calculator-container">
                    <div class="calculator-title">
                        <h4 class="mb-0"><i class="fas fa-calculator me-2"></i>Calculadora Científica</h4>
                    </div>
                    
                    <button class="btn btn-outline-secondary w-100 mb-2" id="modeToggle">
                        <i class="fas fa-exchange-alt me-2"></i>Cambiar a Modo Básico
                    </button>

                    <button class="btn btn-success w-100 mb-3" onclick="transferirResultado()">
                        <i class="fas fa-arrow-right me-2"></i>Transferir a Monto Final
                    </button>
                    
                    <div class="scientific-panel" id="scientificPanel">
                        <div class="row">
                            <div class="col-4 mb-2">
                                <button class="btn btn-outline-primary calculator-btn" onclick="scientificFunction('sin')">sin</button>
                            </div>
                            <div class="col-4 mb-2">
                                <button class="btn btn-outline-primary calculator-btn" onclick="scientificFunction('cos')">cos</button>
                            </div>
                            <div class="col-4 mb-2">
                                <button class="btn btn-outline-primary calculator-btn" onclick="scientificFunction('tan')">tan</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-4 mb-2">
                                <button class="btn btn-outline-primary calculator-btn" onclick="scientificFunction('log')">log</button>
                            </div>
                            <div class="col-4 mb-2">
                                <button class="btn btn-outline-primary calculator-btn" onclick="scientificFunction('ln')">ln</button>
                            </div>
                            <div class="col-4 mb-2">
                                <button class="btn btn-outline-primary calculator-btn" onclick="scientificFunction('sqrt')">√</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-4 mb-2">
                                <button class="btn btn-outline-primary calculator-btn" onclick="appendToDisplay('(')">(</button>
                            </div>
                            <div class="col-4 mb-2">
                                <button class="btn btn-outline-primary calculator-btn" onclick="appendToDisplay(')')">)</button>
                            </div>
                            <div class="col-4 mb-2">
                                <button class="btn btn-outline-primary calculator-btn" onclick="scientificFunction('pi')">π</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="calculator-display bg-light border rounded p-2 mb-2 text-end" id="display">0</div>
                    
                    <div class="row">
                        <div class="col-3 mb-2">
                            <button class="btn btn-danger calculator-btn w-100" onclick="clearDisplay()">C</button>
                        </div>
                        <div class="col-3 mb-2">
                            <button class="btn btn-warning calculator-btn w-100" onclick="deleteLast()">⌫</button>
                        </div>
                        <div class="col-3 mb-2">
                            <button class="btn btn-info calculator-btn w-100" onclick="appendOperation('/')">/</button>
                        </div>
                        <div class="col-3 mb-2">
                            <button class="btn btn-info calculator-btn w-100" onclick="appendOperation('*')">×</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-3 mb-2">
                            <button class="btn btn-secondary calculator-btn w-100" onclick="appendToDisplay('7')">7</button>
                        </div>
                        <div class="col-3 mb-2">
                            <button class="btn btn-secondary calculator-btn w-100" onclick="appendToDisplay('8')">8</button>
                        </div>
                        <div class="col-3 mb-2">
                            <button class="btn btn-secondary calculator-btn w-100" onclick="appendToDisplay('9')">9</button>
                        </div>
                        <div class="col-3 mb-2">
                            <button class="btn btn-info calculator-btn w-100" onclick="appendOperation('-')">-</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-3 mb-2">
                            <button class="btn btn-secondary calculator-btn w-100" onclick="appendToDisplay('4')">4</button>
                        </div>
                        <div class="col-3 mb-2">
                            <button class="btn btn-secondary calculator-btn w-100" onclick="appendToDisplay('5')">5</button>
                        </div>
                        <div class="col-3 mb-2">
                            <button class="btn btn-secondary calculator-btn w-100" onclick="appendToDisplay('6')">6</button>
                        </div>
                        <div class="col-3 mb-2">
                            <button class="btn btn-info calculator-btn w-100" onclick="appendOperation('+')">+</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-3 mb-2">
                            <button class="btn btn-secondary calculator-btn w-100" onclick="appendToDisplay('1')">1</button>
                        </div>
                        <div class="col-3 mb-2">
                            <button class="btn btn-secondary calculator-btn w-100" onclick="appendToDisplay('2')">2</button>
                        </div>
                        <div class="col-3 mb-2">
                            <button class="btn btn-secondary calculator-btn w-100" onclick="appendToDisplay('3')">3</button>
                        </div>
                        <div class="col-3 mb-2">
                            <button class="btn btn-success calculator-btn w-100" onclick="calculate()">=</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-2">
                            <button class="btn btn-secondary calculator-btn w-100" onclick="appendToDisplay('0')">0</button>
                        </div>
                        <div class="col-3 mb-2">
                            <button class="btn btn-secondary calculator-btn w-100" onclick="appendToDisplay('.')">.</button>
                        </div>
                        <div class="col-3 mb-2">
                            <button class="btn btn-outline-primary calculator-btn w-100" onclick="scientificFunction('pow')">x^y</button>
                        </div>
                    </div>
                    
                        <div class="calculator-history mt-3">
                        <h6><i class="fas fa-history me-2"></i>Historial</h6>
                        <div id="history" class="border rounded p-2 bg-light scroll-auto max-h-150"></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    let display = document.getElementById('display');
    let historyElement = document.getElementById('history');
    let currentInput = '0';
    let operator = null;
    let previousInput = '';
    let shouldResetDisplay = false;
    let isScientificMode = true;
    let scientificPanel = document.getElementById('scientificPanel');
    let modeToggle = document.getElementById('modeToggle');

    function updateDisplay() {
        display.textContent = currentInput;
    }

    function appendToDisplay(value) {
        if (shouldResetDisplay) {
            currentInput = '';
            shouldResetDisplay = false;
        }
        
        if (value === '.' && currentInput.includes('.')) {
            return;
        }
        
        if (currentInput === '0' && value !== '.') {
            currentInput = value;
        } else {
            currentInput += value;
        }
        
        updateDisplay();
    }

    function appendOperation(op) {
        if (operator !== null && !shouldResetDisplay) {
            calculate();
        }
        operator = op;
        previousInput = currentInput;
        shouldResetDisplay = true;
    }

    function clearDisplay() {
        currentInput = '0';
        operator = null;
        previousInput = '';
        updateDisplay();
    }

    function deleteLast() {
        if (currentInput.length > 1) {
            currentInput = currentInput.slice(0, -1);
        } else {
            currentInput = '0';
        }
        updateDisplay();
    }

    function calculate() {
        if (operator === null || shouldResetDisplay) {
            return;
        }
        
        let result;
        const prev = parseFloat(previousInput);
        const current = parseFloat(currentInput);
        
        if (isNaN(prev) || isNaN(current)) return;
        
        switch (operator) {
            case '+':
                result = prev + current;
                break;
            case '-':
                result = prev - current;
                break;
            case '*':
                result = prev * current;
                break;
            case '/':
                if (current === 0) {
                    alert("Error: División por cero");
                    return;
                }
                result = prev / current;
                break;
            case '^':
                result = Math.pow(prev, current);
                break;
            default:
                return;
        }
        
        // Agregar al historial
        addToHistory(`${previousInput} ${operator} ${currentInput} = ${result}`);
        
        currentInput = result.toString();
        operator = null;
        previousInput = '';
        shouldResetDisplay = true;
        updateDisplay();
    }

    function scientificFunction(func) {
        let result;
        let value = parseFloat(currentInput);
        
        if (isNaN(value) && func !== 'pi') {
            return;
        }
        
        switch(func) {
            case 'sin':
                result = Math.sin(value * Math.PI / 180);
                addToHistory(`sin(${value}) = ${result}`);
                break;
            case 'cos':
                result = Math.cos(value * Math.PI / 180);
                addToHistory(`cos(${value}) = ${result}`);
                break;
            case 'tan':
                result = Math.tan(value * Math.PI / 180);
                addToHistory(`tan(${value}) = ${result}`);
                break;
            case 'log':
                if (value <= 0) {
                    alert("Error: Logaritmo de número no positivo");
                    return;
                }
                result = Math.log10(value);
                addToHistory(`log(${value}) = ${result}`);
                break;
            case 'ln':
                if (value <= 0) {
                    alert("Error: Logaritmo natural de número no positivo");
                    return;
                }
                result = Math.log(value);
                addToHistory(`ln(${value}) = ${result}`);
                break;
            case 'sqrt':
                if (value < 0) {
                    alert("Error: Raíz cuadrada de número negativo");
                    return;
                }
                result = Math.sqrt(value);
                addToHistory(`√${value} = ${result}`);
                break;
            case 'pi':
                result = Math.PI;
                addToHistory(`π = ${result}`);
                break;
            case 'pow':
                operator = '^';
                previousInput = currentInput;
                shouldResetDisplay = true;
                return;
        }
        
        currentInput = result.toString();
        shouldResetDisplay = true;
        updateDisplay();
    }

    function addToHistory(operation) {
        const historyItem = document.createElement('div');
        historyItem.className = 'history-item small';
        historyItem.textContent = operation;
        historyElement.prepend(historyItem);
        
        // Limitar el historial a 5 elementos
        if (historyElement.children.length > 5) {
            historyElement.removeChild(historyElement.lastChild);
        }
    }

    function transferirResultado() {
        const resultado = display.textContent;
        const montoFinal = document.getElementById('monto_final_real');
        
        if (montoFinal) {
            montoFinal.value = resultado;
        }
        
        // Mostrar confirmación
        alert(`Resultado ${resultado} transferido al campo de monto final`);
    }

    function toggleMode() {
        isScientificMode = !isScientificMode;
        if (isScientificMode) {
            scientificPanel.style.display = 'block';
            modeToggle.innerHTML = '<i class="fas fa-exchange-alt me-2"></i>Cambiar a Modo Básico';
        } else {
            scientificPanel.style.display = 'none';
            modeToggle.innerHTML = '<i class="fas fa-exchange-alt me-2"></i>Cambiar a Modo Científico';
        }
    }

    // Inicializar eventos
    modeToggle.addEventListener('click', toggleMode);

    // Manejo de eventos de teclado
    document.addEventListener('keydown', function(event) {
        const key = event.key;
        
        if (/[0-9]/.test(key)) {
            appendToDisplay(key);
        } else if (key === '.') {
            appendToDisplay('.');
        } else if (key === '+') {
            appendOperation('+');
        } else if (key === '-') {
            appendOperation('-');
        } else if (key === '*') {
            appendOperation('*');
        } else if (key === '/') {
            event.preventDefault();
            appendOperation('/');
        } else if (key === 'Enter' || key === '=') {
            event.preventDefault();
            calculate();
        } else if (key === 'Escape' || key === 'Delete') {
            clearDisplay();
        } else if (key === 'Backspace') {
            deleteLast();
        } else if (key === '^') {
            scientificFunction('pow');
        }
    });

    // Inicializar la calculadora
    updateDisplay();
</script>