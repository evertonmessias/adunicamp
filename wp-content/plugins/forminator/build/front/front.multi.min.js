(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorParser = _interopRequireDefault(require("./parser/front.calculator.parser.tokenizer"));

var _frontCalculatorSymbol = _interopRequireDefault(require("./symbol/front.calculator.symbol.loader"));

var _frontCalculator = _interopRequireDefault(require("./parser/front.calculator.parser"));

var _frontCalculatorSymbol2 = _interopRequireDefault(require("./symbol/front.calculator.symbol.number"));

var _frontCalculatorSymbolConstant = _interopRequireDefault(require("./symbol/abstract/front.calculator.symbol.constant.abstract"));

var _frontCalculatorParserNode = _interopRequireDefault(require("./parser/node/front.calculator.parser.node.symbol"));

var _frontCalculatorSymbolOperator = _interopRequireDefault(require("./symbol/abstract/front.calculator.symbol.operator.abstract"));

var _frontCalculatorSymbol3 = _interopRequireDefault(require("./symbol/front.calculator.symbol.separator"));

var _frontCalculatorParserNode2 = _interopRequireDefault(require("./parser/node/front.calculator.parser.node.function"));

var _frontCalculatorParserNode3 = _interopRequireDefault(require("./parser/node/front.calculator.parser.node.container"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

var FrontCalculator = /*#__PURE__*/function () {
  /**
   *
   * @param {string} term
   */
  function FrontCalculator(term) {
    _classCallCheck(this, FrontCalculator);

    /**
     *
     * @type {string}
     */
    this.term = term;
    /**
     *
     * @type {FrontCalculatorParserTokenizer}
     */

    this.tokenizer = new _frontCalculatorParser.default(this.term);
    /**
     *
     * @type {FrontCalculatorSymbolLoader}
     */

    this.symbolLoader = new _frontCalculatorSymbol.default();
    /**
     *
     * @type {FrontCalculatorParser}
     */

    this.parser = new _frontCalculator.default(this.symbolLoader);
  }
  /**
   *
   * @returns {FrontCalculatorParserNodeContainer}
   */


  _createClass(FrontCalculator, [{
    key: "parse",
    value: function parse() {
      // reset
      this.tokenizer.input = this.term;
      this.tokenizer.reset();
      var tokens = this.tokenizer.tokenize();

      if (tokens.length === 0) {
        throw 'Error: Empty token of calculator term.';
      }

      var rootNode = this.parser.parse(tokens);

      if (rootNode.isEmpty()) {
        throw 'Error: Empty nodes of calculator tokens.';
      }

      return rootNode;
    }
    /**
     *
     * @returns {number}
     */

  }, {
    key: "calculate",
    value: function calculate() {
      var result = 0;
      var rootNode = this.parse();

      if (false === rootNode) {
        return result;
      }

      return this.calculateNode(rootNode);
    }
    /**
     *Calculates the numeric value / result of a node of
     * any known and calculable type. (For example symbol
     * nodes with a symbol of type separator are not
     * calculable.)
     *
     * @param {FrontCalculatorParserNodeAbstract} node
     *
     * @returns {number}
     */

  }, {
    key: "calculateNode",
    value: function calculateNode(node) {
      if (node instanceof _frontCalculatorParserNode.default) {
        return this.calculateSymbolNode(node);
      } else if (node instanceof _frontCalculatorParserNode2.default) {
        return this.calculateFunctionNode(node);
      } else if (node instanceof _frontCalculatorParserNode3.default) {
        return this.calculateContainerNode(node);
      } else {
        throw 'Error: Cannot calculate node of unknown type "' + node.constructor.name + '"';
      }
    }
    /**
     * This method actually calculates the results of every sub-terms
     * in the syntax tree (which consists of nodes).
     * It can call itself recursively.
     * Attention: $node must not be of type FunctionNode!
     *
     * @param {FrontCalculatorParserNodeContainer} containerNode
     *
     * @returns {number}
     */

  }, {
    key: "calculateContainerNode",
    value: function calculateContainerNode(containerNode) {
      if (containerNode instanceof _frontCalculatorParserNode2.default) {
        throw 'Error: Expected container node but got a function node';
      }

      var result = 0;
      var nodes = containerNode.childNodes;
      var orderedOperatorNodes = this.detectCalculationOrder(nodes); // Actually calculate the term. Iterates over the ordered operators and
      // calculates them, then replaces the parts of the operation by the result.

      for (var i = 0; i < orderedOperatorNodes.length; i++) {
        var operatorNode = orderedOperatorNodes[i].node;
        var index = orderedOperatorNodes[i].index;
        var leftOperand = null;
        var leftOperandIndex = null;
        var nodeIndex = 0;

        while (nodeIndex !== index) {
          if (nodes[nodeIndex] === undefined) {
            nodeIndex++;
            continue;
          }

          leftOperand = nodes[nodeIndex];
          leftOperandIndex = nodeIndex;
          nodeIndex++;
        }

        nodeIndex++;

        while (nodes[nodeIndex] === undefined) {
          nodeIndex++;
        }

        var rightOperand = nodes[nodeIndex];
        var rightOperandIndex = nodeIndex;
        var rightNumber = !isNaN(rightOperand) ? rightOperand : this.calculateNode(rightOperand);
        /**
         * @type {FrontCalculatorSymbolOperatorAbstract}
         */

        var symbol = operatorNode.symbol;

        if (operatorNode.isUnaryOperator) {
          result = symbol.operate(null, rightNumber); // Replace the participating symbols of the operation by the result

          delete nodes[rightOperandIndex]; // `delete` operation only set the value to empty, not `actually` remove it

          nodes[index] = result;
        } else {
          if (leftOperandIndex !== null && leftOperand !== null) {
            var leftNumber = !isNaN(leftOperand) ? leftOperand : this.calculateNode(leftOperand);
            result = symbol.operate(leftNumber, rightNumber); // Replace the participating symbols of the operation by the result

            delete nodes[leftOperandIndex];
            delete nodes[rightOperandIndex];
            nodes[index] = result;
          }
        }
      } //cleanup empty nodes


      nodes = nodes.filter(function (node) {
        return node !== undefined;
      });

      if (nodes.length === 0) {
        throw 'Error: Missing calculable subterm. Are there empty brackets?';
      }

      if (nodes.length > 1) {
        throw 'Error: Missing operators between parts of the term.';
      } // The only remaining element of the $nodes array contains the overall result


      result = nodes.pop(); // If the $nodes array did not contain any operator (but only one node) than
      // the result of this node has to be calculated now

      if (isNaN(result)) {
        return this.calculateNode(result);
      }

      return result;
    }
    /**
     * Returns the numeric value of a function node.
     * @param {FrontCalculatorParserNodeFunction} functionNode
     *
     * @returns {number}
     */

  }, {
    key: "calculateFunctionNode",
    value: function calculateFunctionNode(functionNode) {
      var nodes = functionNode.childNodes;
      var functionArguments = []; // ex : func(1+2,3,4) : 1+2 need to be calculated first

      var argumentChildNodes = [];
      var containerNode = null;

      for (var i = 0; i < nodes.length; i++) {
        var node = nodes[i];

        if (node instanceof _frontCalculatorParserNode.default) {
          if (node.symbol instanceof _frontCalculatorSymbol3.default) {
            containerNode = new _frontCalculatorParserNode3.default(argumentChildNodes);
            functionArguments.push(this.calculateNode(containerNode));
            argumentChildNodes = [];
          } else {
            argumentChildNodes.push(node);
          }
        } else {
          argumentChildNodes.push(node);
        }
      }

      if (argumentChildNodes.length > 0) {
        containerNode = new _frontCalculatorParserNode3.default(argumentChildNodes);
        functionArguments.push(this.calculateNode(containerNode));
      }
      /**
       *
       * @type {FrontCalculatorSymbolFunctionAbstract}
       */


      var symbol = functionNode.symbolNode.symbol;
      return symbol.execute(functionArguments);
    }
    /**
     * Returns the numeric value of a symbol node.
     * Attention: node.symbol must not be of type AbstractOperator!
     *
     * @param {FrontCalculatorParserNodeSymbol} symbolNode
     *
     * @returns {Number}
     */

  }, {
    key: "calculateSymbolNode",
    value: function calculateSymbolNode(symbolNode) {
      var symbol = symbolNode.symbol;
      var number = 0;

      if (symbol instanceof _frontCalculatorSymbol2.default) {
        number = symbolNode.token.value; // Convert string to int or float (depending on the type of the number)
        // If the number has a longer fractional part, it will be cut.

        number = Number(number);
      } else if (symbol instanceof _frontCalculatorSymbolConstant.default) {
        number = symbol.value;
      } else {
        throw 'Error: Found symbol of unexpected type "' + symbol.constructor.name + '", expected number or constant';
      }

      return number;
    }
    /**
     * Detect the calculation order of a given array of nodes.
     * Does only care for the precedence of operators.
     * Does not care for child nodes of container nodes.
     * Returns a new array with ordered symbol nodes
     *
     * @param {FrontCalculatorParserNodeAbstract[]} nodes
     *
     * @return {Array}
     */

  }, {
    key: "detectCalculationOrder",
    value: function detectCalculationOrder(nodes) {
      var operatorNodes = []; // Store all symbol nodes that have a symbol of type abstract operator in an array

      for (var i = 0; i < nodes.length; i++) {
        var node = nodes[i];

        if (node instanceof _frontCalculatorParserNode.default) {
          if (node.symbol instanceof _frontCalculatorSymbolOperator.default) {
            var operatorNode = {
              index: i,
              node: node
            };
            operatorNodes.push(operatorNode);
          }
        }
      }

      operatorNodes.sort(
      /**
       * Returning 1 means $nodeTwo before $nodeOne, returning -1 means $nodeOne before $nodeTwo.
       * @param {Object} operatorNodeOne
       * @param {Object} operatorNodeTwo
       */
      function (operatorNodeOne, operatorNodeTwo) {
        var nodeOne = operatorNodeOne.node;
        var nodeTwo = operatorNodeTwo.node; // First-level precedence of node one

        /**
         *
         * @type {FrontCalculatorSymbolOperatorAbstract}
         */

        var symbolOne = nodeOne.symbol;
        var precedenceOne = 2;

        if (nodeOne.isUnaryOperator) {
          precedenceOne = 3;
        } // First-level precedence of node two

        /**
         *
         * @type {FrontCalculatorSymbolOperatorAbstract}
         */


        var symbolTwo = nodeTwo.symbol;
        var precedenceTwo = 2;

        if (nodeTwo.isUnaryOperator) {
          precedenceTwo = 3;
        } // If the first-level precedence is the same, compare the second-level precedence


        if (precedenceOne === precedenceTwo) {
          precedenceOne = symbolOne.precedence;
          precedenceTwo = symbolTwo.precedence;
        } // If the second-level precedence is the same, we have to ensure that the sorting algorithm does
        // insert the node / token that is left in the term before the node / token that is right.
        // Therefore we cannot return 0 but compare the positions and return 1 / -1.


        if (precedenceOne === precedenceTwo) {
          return nodeOne.token.position < nodeTwo.token.position ? -1 : 1;
        }

        return precedenceOne < precedenceTwo ? 1 : -1;
      });
      return operatorNodes;
    }
  }]);

  return FrontCalculator;
}();

exports.default = FrontCalculator;

if (window['forminatorCalculator'] === undefined) {
  window.forminatorCalculator = function (term) {
    return new FrontCalculator(term);
  };
}

},{"./parser/front.calculator.parser":2,"./parser/front.calculator.parser.tokenizer":4,"./parser/node/front.calculator.parser.node.container":6,"./parser/node/front.calculator.parser.node.function":7,"./parser/node/front.calculator.parser.node.symbol":8,"./symbol/abstract/front.calculator.symbol.constant.abstract":10,"./symbol/abstract/front.calculator.symbol.operator.abstract":12,"./symbol/front.calculator.symbol.loader":16,"./symbol/front.calculator.symbol.number":17,"./symbol/front.calculator.symbol.separator":18}],2:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorParser = _interopRequireDefault(require("./front.calculator.parser.token"));

var _frontCalculatorSymbol = _interopRequireDefault(require("../symbol/front.calculator.symbol.number"));

var _frontCalculatorSymbolOpening = _interopRequireDefault(require("../symbol/brackets/front.calculator.symbol.opening.bracket"));

var _frontCalculatorSymbolClosing = _interopRequireDefault(require("../symbol/brackets/front.calculator.symbol.closing.bracket"));

var _frontCalculatorSymbolFunction = _interopRequireDefault(require("../symbol/abstract/front.calculator.symbol.function.abstract"));

var _frontCalculatorSymbolOperator = _interopRequireDefault(require("../symbol/abstract/front.calculator.symbol.operator.abstract"));

var _frontCalculatorSymbol2 = _interopRequireDefault(require("../symbol/front.calculator.symbol.separator"));

var _frontCalculatorParserNode = _interopRequireDefault(require("./node/front.calculator.parser.node.symbol"));

var _frontCalculatorParserNode2 = _interopRequireDefault(require("./node/front.calculator.parser.node.container"));

var _frontCalculatorParserNode3 = _interopRequireDefault(require("./node/front.calculator.parser.node.function"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

/**
 * The parsers has one important method: parse()
 * It takes an array of tokens as input and
 * returns an array of nodes as output.
 * These nodes are the syntax tree of the term.
 *
 */
var FrontCalculatorParser = /*#__PURE__*/function () {
  /**
   *
   * @param {FrontCalculatorSymbolLoader} symbolLoader
   */
  function FrontCalculatorParser(symbolLoader) {
    _classCallCheck(this, FrontCalculatorParser);

    /**
     *
     * @type {FrontCalculatorSymbolLoader}
     */
    this.symbolLoader = symbolLoader;
  }
  /**
   * Parses an array with tokens. Returns an array of nodes.
   * These nodes define a syntax tree.
   *
   * @param {FrontCalculatorParserToken[]} tokens
   *
   * @returns FrontCalculatorParserNodeContainer
   */


  _createClass(FrontCalculatorParser, [{
    key: "parse",
    value: function parse(tokens) {
      var symbolNodes = this.detectSymbols(tokens);
      var nodes = this.createTreeByBrackets(symbolNodes);
      nodes = this.transformTreeByFunctions(nodes);
      this.checkGrammar(nodes); // Wrap the nodes in an array node.

      return new _frontCalculatorParserNode2.default(nodes);
    }
    /**
     * Creates a flat array of symbol nodes from tokens.
     *
     * @param {FrontCalculatorParserToken[]} tokens
     * @returns {FrontCalculatorParserNodeSymbol[]}
     */

  }, {
    key: "detectSymbols",
    value: function detectSymbols(tokens) {
      var symbolNodes = [];
      var symbol = null;
      var identifier = null;
      var expectingOpeningBracket = false; // True if we expect an opening bracket (after a function name)

      var openBracketCounter = 0;

      for (var i = 0; i < tokens.length; i++) {
        var token = tokens[i];
        var type = token.type;

        if (_frontCalculatorParser.default.TYPE_WORD === type) {
          identifier = token.value;
          symbol = this.symbolLoader.find(identifier);

          if (null === symbol) {
            throw 'Error: Detected unknown or invalid string identifier: ' + identifier + '.';
          }
        } else if (type === _frontCalculatorParser.default.TYPE_NUMBER) {
          // Notice: Numbers do not have an identifier
          var symbolNumbers = this.symbolLoader.findSubTypes(_frontCalculatorSymbol.default);

          if (symbolNumbers.length < 1 || !(symbolNumbers instanceof Array)) {
            throw 'Error: Unavailable number symbol processor.';
          }

          symbol = symbolNumbers[0];
        } else {
          // Type Token::TYPE_CHARACTER:
          identifier = token.value;
          symbol = this.symbolLoader.find(identifier);

          if (null === symbol) {
            throw 'Error: Detected unknown or invalid string identifier: ' + identifier + '.';
          }

          if (symbol instanceof _frontCalculatorSymbolOpening.default) {
            openBracketCounter++;
          }

          if (symbol instanceof _frontCalculatorSymbolClosing.default) {
            openBracketCounter--; // Make sure there are not too many closing brackets

            if (openBracketCounter < 0) {
              throw 'Error: Found closing bracket that does not have an opening bracket.';
            }
          }
        }

        if (expectingOpeningBracket) {
          if (!(symbol instanceof _frontCalculatorSymbolOpening.default)) {
            throw 'Error: Expected opening bracket (after a function) but got something else.';
          }

          expectingOpeningBracket = false;
        } else {
          if (symbol instanceof _frontCalculatorSymbolFunction.default) {
            expectingOpeningBracket = true;
          }
        }

        var symbolNode = new _frontCalculatorParserNode.default(token, symbol);
        symbolNodes.push(symbolNode);
      } // Make sure the term does not end with the name of a function but without an opening bracket


      if (expectingOpeningBracket) {
        throw 'Error: Expected opening bracket (after a function) but reached the end of the term';
      } // Make sure there are not too many opening brackets


      if (openBracketCounter > 0) {
        throw 'Error: There is at least one opening bracket that does not have a closing bracket';
      }

      return symbolNodes;
    }
    /**
     * Expects a flat array of symbol nodes and (if possible) transforms
     * it to a tree of nodes. Cares for brackets.
     * Attention: Expects valid brackets!
     * Check the brackets before you call this method.
     *
     * @param {FrontCalculatorParserNodeSymbol[]} symbolNodes
     * @returns {FrontCalculatorParserNodeAbstract[]}
     */

  }, {
    key: "createTreeByBrackets",
    value: function createTreeByBrackets(symbolNodes) {
      var tree = [];
      var nodesInBracket = []; // AbstractSymbol nodes inside level-0-brackets

      var openBracketCounter = 0;

      for (var i = 0; i < symbolNodes.length; i++) {
        var symbolNode = symbolNodes[i];

        if (!(symbolNode instanceof _frontCalculatorParserNode.default)) {
          throw 'Error: Expected symbol node, but got "' + symbolNode.constructor.name + '"';
        }

        if (symbolNode.symbol instanceof _frontCalculatorSymbolOpening.default) {
          openBracketCounter++;

          if (openBracketCounter > 1) {
            nodesInBracket.push(symbolNode);
          }
        } else if (symbolNode.symbol instanceof _frontCalculatorSymbolClosing.default) {
          openBracketCounter--; // Found a closing bracket on level 0

          if (0 === openBracketCounter) {
            var subTree = this.createTreeByBrackets(nodesInBracket); // Subtree can be empty for example if the term looks like this: "()" or "functioname()"
            // But this is okay, we need to allow this so we can call functions without a parameter

            tree.push(new _frontCalculatorParserNode2.default(subTree));
            nodesInBracket = [];
          } else {
            nodesInBracket.push(symbolNode);
          }
        } else {
          if (0 === openBracketCounter) {
            tree.push(symbolNode);
          } else {
            nodesInBracket.push(symbolNode);
          }
        }
      }

      return tree;
    }
    /**
     * Replaces [a SymbolNode that has a symbol of type AbstractFunction,
     * followed by a node of type ContainerNode] by a FunctionNode.
     * Expects the $nodes not including any function nodes (yet).
     *
     * @param {FrontCalculatorParserNodeAbstract[]} nodes
     *
     * @returns {FrontCalculatorParserNodeAbstract[]}
     */

  }, {
    key: "transformTreeByFunctions",
    value: function transformTreeByFunctions(nodes) {
      var transformedNodes = [];
      var functionSymbolNode = null;

      for (var i = 0; i < nodes.length; i++) {
        var node = nodes[i];

        if (node instanceof _frontCalculatorParserNode2.default) {
          var transformedChildNodes = this.transformTreeByFunctions(node.childNodes);

          if (null !== functionSymbolNode) {
            var functionNode = new _frontCalculatorParserNode3.default(transformedChildNodes, functionSymbolNode);
            transformedNodes.push(functionNode);
            functionSymbolNode = null;
          } else {
            // not a function
            node.childNodes = transformedChildNodes;
            transformedNodes.push(node);
          }
        } else if (node instanceof _frontCalculatorParserNode.default) {
          var symbol = node.symbol;

          if (symbol instanceof _frontCalculatorSymbolFunction.default) {
            functionSymbolNode = node;
          } else {
            transformedNodes.push(node);
          }
        } else {
          throw 'Error: Expected array node or symbol node, got "' + node.constructor.name + '"';
        }
      }

      return transformedNodes;
    }
    /**
     * Ensures the tree follows the grammar rules for terms
     *
     * @param {FrontCalculatorParserNodeAbstract[]} nodes
     */

  }, {
    key: "checkGrammar",
    value: function checkGrammar(nodes) {
      // TODO Make sure that separators are only in the child nodes of the array node of a function node
      // (If this happens the calculator will throw an exception)
      for (var i = 0; i < nodes.length; i++) {
        var node = nodes[i];

        if (node instanceof _frontCalculatorParserNode.default) {
          var symbol = node.symbol;

          if (symbol instanceof _frontCalculatorSymbolOperator.default) {
            var posOfRightOperand = i + 1; // Make sure the operator is positioned left of a (potential) operand (=prefix notation).
            // Example term: "-1"

            if (posOfRightOperand >= nodes.length) {
              throw 'Error: Found operator that does not stand before an operand.';
            }

            var posOfLeftOperand = i - 1;
            var leftOperand = null; // Operator is unary if positioned at the beginning of a term

            if (posOfLeftOperand >= 0) {
              leftOperand = nodes[posOfLeftOperand];

              if (leftOperand instanceof _frontCalculatorParserNode.default) {
                if (leftOperand.symbol instanceof _frontCalculatorSymbolOperator.default // example 1`+-`5 : + = operator, - = unary
                || leftOperand.symbol instanceof _frontCalculatorSymbol2.default // example func(1`,-`5) ,= separator, - = unary
                ) {
                  // Operator is unary if positioned right to another operator
                  leftOperand = null;
                }
              }
            } // If null, the operator is unary


            if (null === leftOperand) {
              if (!symbol.operatesUnary) {
                throw 'Error: Found operator in unary notation that is not unary.';
              } // Remember that this node represents a unary operator


              node.setIsUnaryOperator(true);
            } else {
              if (!symbol.operatesBinary) {
                console.log(symbol);
                throw 'Error: Found operator in binary notation that is not binary.';
              }
            }
          }
        } else {
          this.checkGrammar(node.childNodes);
        }
      }
    }
  }]);

  return FrontCalculatorParser;
}();

exports.default = FrontCalculatorParser;

},{"../symbol/abstract/front.calculator.symbol.function.abstract":11,"../symbol/abstract/front.calculator.symbol.operator.abstract":12,"../symbol/brackets/front.calculator.symbol.closing.bracket":13,"../symbol/brackets/front.calculator.symbol.opening.bracket":14,"../symbol/front.calculator.symbol.number":17,"../symbol/front.calculator.symbol.separator":18,"./front.calculator.parser.token":3,"./node/front.calculator.parser.node.container":6,"./node/front.calculator.parser.node.function":7,"./node/front.calculator.parser.node.symbol":8}],3:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

var FrontCalculatorParserToken = /*#__PURE__*/function () {
  function FrontCalculatorParserToken(type, value, position) {
    _classCallCheck(this, FrontCalculatorParserToken);

    /**
     *
     * @type {Number}
     */
    this.type = type;
    /**
     *
     * @type {String|Number}
     */

    this.value = value;
    /**
     *
     * @type {Number}
     */

    this.position = position;
  }

  _createClass(FrontCalculatorParserToken, null, [{
    key: "TYPE_WORD",
    get: function get() {
      return 1;
    }
  }, {
    key: "TYPE_CHAR",
    get: function get() {
      return 2;
    }
  }, {
    key: "TYPE_NUMBER",
    get: function get() {
      return 3;
    }
  }]);

  return FrontCalculatorParserToken;
}();

exports.default = FrontCalculatorParserToken;

},{}],4:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorParser = _interopRequireDefault(require("./front.calculator.parser.token"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

var FrontCalculatorParserTokenizer = /*#__PURE__*/function () {
  function FrontCalculatorParserTokenizer(input) {
    _classCallCheck(this, FrontCalculatorParserTokenizer);

    /**
     *
     * @type {String}
     */
    this.input = input;
    /**
     * @type {number}
     */

    this.currentPosition = 0;
  }
  /**
   *
   * @returns {FrontCalculatorParserToken[]}
   */


  _createClass(FrontCalculatorParserTokenizer, [{
    key: "tokenize",
    value: function tokenize() {
      this.reset();
      var tokens = [];
      var token = this.readToken();

      while (token) {
        tokens.push(token);
        token = this.readToken();
      }

      return tokens;
    }
    /**
     *
     * @returns {FrontCalculatorParserToken}
     */

  }, {
    key: "readToken",
    value: function readToken() {
      this.stepOverWhitespace();
      var char = this.readCurrent();

      if (null === char) {
        return null;
      }

      var value = null;
      var type = null;

      if (this.isLetter(char)) {
        value = this.readWord();
        type = _frontCalculatorParser.default.TYPE_WORD;
      } else if (this.isDigit(char) || this.isPeriod(char)) {
        value = this.readNumber();
        type = _frontCalculatorParser.default.TYPE_NUMBER;
      } else {
        value = this.readChar();
        type = _frontCalculatorParser.default.TYPE_CHAR;
      }

      return new _frontCalculatorParser.default(type, value, this.currentPosition);
    }
    /**
     * Returns true, if a given character is a letter (a-z and A-Z).
     *
     * @param char
     * @returns {boolean}
     */

  }, {
    key: "isLetter",
    value: function isLetter(char) {
      if (null === char) {
        return false;
      }

      var ascii = char.charCodeAt(0);
      /**
       * ASCII codes: 65 = 'A', 90 = 'Z', 97 = 'a', 122 = 'z'--
       **/

      return ascii >= 65 && ascii <= 90 || ascii >= 97 && ascii <= 122;
    }
    /**
     * Returns true, if a given character is a digit (0-9).
     *
     * @param char
     * @returns {boolean}
     */

  }, {
    key: "isDigit",
    value: function isDigit(char) {
      if (null === char) {
        return false;
      }

      var ascii = char.charCodeAt(0);
      /**
       * ASCII codes: 48 = '0', 57 = '9'
       */

      return ascii >= 48 && ascii <= 57;
    }
    /**
     * Returns true, if a given character is a period ('.').
     *
     * @param char
     * @returns {boolean}
     */

  }, {
    key: "isPeriod",
    value: function isPeriod(char) {
      return '.' === char;
    }
    /**
     * Returns true, if a given character is whitespace.
     * Notice: A null char is not seen as whitespace.
     *
     * @param char
     * @returns {boolean}
     */

  }, {
    key: "isWhitespace",
    value: function isWhitespace(char) {
      return [" ", "\t", "\n"].indexOf(char) >= 0;
    }
  }, {
    key: "stepOverWhitespace",
    value: function stepOverWhitespace() {
      while (this.isWhitespace(this.readCurrent())) {
        this.readNext();
      }
    }
    /**
     * Reads a word. Assumes that the cursor of the input stream
     * currently is positioned at the beginning of a word.
     *
     * @returns {string}
     */

  }, {
    key: "readWord",
    value: function readWord() {
      var word = '';
      var char = this.readCurrent(); // Try to read the word

      while (null !== char) {
        if (this.isLetter(char)) {
          word += char;
        } else {
          break;
        } // Just move the cursor to the next position


        char = this.readNext();
      }

      return word;
    }
    /**
     * Reads a number (as a string). Assumes that the cursor
     * of the input stream currently is positioned at the
     * beginning of a number.
     *
     * @returns {string}
     */

  }, {
    key: "readNumber",
    value: function readNumber() {
      var number = '';
      var foundPeriod = false; // Try to read the number.
      // Notice: It does not matter if the number only consists of a single period
      // or if it ends with a period.

      var char = this.readCurrent();

      while (null !== char) {
        if (this.isPeriod(char) || this.isDigit(char)) {
          if (this.isPeriod(char)) {
            if (foundPeriod) {
              throw 'Error: A number cannot have more than one period';
            }

            foundPeriod = true;
          }

          number += char;
        } else {
          break;
        } // read next


        char = this.readNext();
      }

      return number;
    }
    /**
     * Reads a single char. Assumes that the cursor of the input stream
     * currently is positioned at a char (not on null).
     *
     * @returns {String}
     */

  }, {
    key: "readChar",
    value: function readChar() {
      var char = this.readCurrent(); // Just move the cursor to the next position

      this.readNext();
      return char;
    }
    /**
     *
     * @returns {String|null}
     */

  }, {
    key: "readCurrent",
    value: function readCurrent() {
      var char = null;

      if (this.hasCurrent()) {
        char = this.input[this.currentPosition];
      }

      return char;
    }
    /**
     * Move the the cursor to the next position.
     * Will always move the cursor, even if the end of the string has been passed.
     *
     * @returns {String}
     */

  }, {
    key: "readNext",
    value: function readNext() {
      this.currentPosition++;
      return this.readCurrent();
    }
    /**
     * Returns true if there is a character at the current position
     *
     * @returns {boolean}
     */

  }, {
    key: "hasCurrent",
    value: function hasCurrent() {
      return this.currentPosition < this.input.length;
    }
  }, {
    key: "reset",
    value: function reset() {
      this.currentPosition = 0;
    }
  }]);

  return FrontCalculatorParserTokenizer;
}();

exports.default = FrontCalculatorParserTokenizer;

},{"./front.calculator.parser.token":3}],5:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var FrontCalculatorParserNodeAbstract = /*#__PURE__*/_createClass(function FrontCalculatorParserNodeAbstract() {
  _classCallCheck(this, FrontCalculatorParserNodeAbstract);
});

exports.default = FrontCalculatorParserNodeAbstract;

},{}],6:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorParserNode = _interopRequireDefault(require("./front.calculator.parser.node.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * A parent node is a container for a (sorted) array of nodes.
 *
 */
var FrontCalculatorParserNodeContainer = /*#__PURE__*/function (_FrontCalculatorParse) {
  _inherits(FrontCalculatorParserNodeContainer, _FrontCalculatorParse);

  var _super = _createSuper(FrontCalculatorParserNodeContainer);

  function FrontCalculatorParserNodeContainer(childNodes) {
    var _this;

    _classCallCheck(this, FrontCalculatorParserNodeContainer);

    _this = _super.call(this);
    /**
     *
     * @type {FrontCalculatorParserNodeAbstract[]}
     */

    _this.childNodes = null;

    _this.setChildNodes(childNodes);

    return _this;
  }
  /**
   * Setter for the child nodes.
   * Notice: The number of child nodes can be 0.
   * @param childNodes
   */


  _createClass(FrontCalculatorParserNodeContainer, [{
    key: "setChildNodes",
    value: function setChildNodes(childNodes) {
      childNodes.forEach(function (childNode) {
        if (!(childNode instanceof _frontCalculatorParserNode.default)) {
          throw 'Expected AbstractNode, but got ' + childNode.constructor.name;
        }
      });
      this.childNodes = childNodes;
    }
    /**
     * Returns the number of child nodes in this array node.
     * Does not count the child nodes of the child nodes.
     *
     * @returns {number}
     */

  }, {
    key: "size",
    value: function size() {
      try {
        return this.childNodes.length;
      } catch (e) {
        return 0;
      }
    }
    /**
     * Returns true if the array node does not have any
     * child nodes. This might sound strange but is possible.
     *
     * @returns {boolean}
     */

  }, {
    key: "isEmpty",
    value: function isEmpty() {
      return !this.size();
    }
  }]);

  return FrontCalculatorParserNodeContainer;
}(_frontCalculatorParserNode.default);

exports.default = FrontCalculatorParserNodeContainer;

},{"./front.calculator.parser.node.abstract":5}],7:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorParserNode = _interopRequireDefault(require("./front.calculator.parser.node.container"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * A function in a term consists of the name of the function
 * (the symbol of the function) and the brackets that follow
 * the name and everything that is in this brackets (the
 * arguments). A function node combines these two things.
 * It stores its symbol in the $symbolNode property and its
 * arguments in the $childNodes property which is inherited
 * from the ContainerNode class.
 *
 */
var FrontCalculatorParserNodeFunction = /*#__PURE__*/function (_FrontCalculatorParse) {
  _inherits(FrontCalculatorParserNodeFunction, _FrontCalculatorParse);

  var _super = _createSuper(FrontCalculatorParserNodeFunction);

  /**
   * ContainerNode constructor.
   * Attention: The constructor is differs from the constructor
   * of the parent class!
   *
   * @param childNodes
   * @param symbolNode
   */
  function FrontCalculatorParserNodeFunction(childNodes, symbolNode) {
    var _this;

    _classCallCheck(this, FrontCalculatorParserNodeFunction);

    _this = _super.call(this, childNodes);
    /**
     *
     * @type {FrontCalculatorParserNodeSymbol}
     */

    _this.symbolNode = symbolNode;
    return _this;
  }

  return _createClass(FrontCalculatorParserNodeFunction);
}(_frontCalculatorParserNode.default);

exports.default = FrontCalculatorParserNodeFunction;

},{"./front.calculator.parser.node.container":6}],8:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolOperator = _interopRequireDefault(require("../../symbol/abstract/front.calculator.symbol.operator.abstract"));

var _frontCalculatorParserNode = _interopRequireDefault(require("./front.calculator.parser.node.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * A symbol node is a node in the syntax tree.
 * Leaf nodes do not have any child nodes
 * (parent nodes can have child nodes). A
 * symbol node represents a mathematical symbol.
 * Nodes are created by the parser.
 *
 */
var FrontCalculatorParserNodeSymbol = /*#__PURE__*/function (_FrontCalculatorParse) {
  _inherits(FrontCalculatorParserNodeSymbol, _FrontCalculatorParse);

  var _super = _createSuper(FrontCalculatorParserNodeSymbol);

  function FrontCalculatorParserNodeSymbol(token, symbol) {
    var _this;

    _classCallCheck(this, FrontCalculatorParserNodeSymbol);

    _this = _super.call(this);
    /**
     * The token of the node. It contains the value.
     *
     * @type {FrontCalculatorParserToken}
     */

    _this.token = token;
    /**
     * The symbol of the node. It defines the type of the node.
     *
     * @type {FrontCalculatorSymbolAbstract}
     */

    _this.symbol = symbol;
    /**
     * Unary operators need to be treated specially.
     * Therefore a node has to know if it (or to be
     * more precise the symbol of the node)
     * represents a unary operator.
     * Example : -1, -4
     *
     * @type {boolean}
     */

    _this.isUnaryOperator = false;
    return _this;
  }

  _createClass(FrontCalculatorParserNodeSymbol, [{
    key: "setIsUnaryOperator",
    value: function setIsUnaryOperator(isUnaryOperator) {
      if (!(this.symbol instanceof _frontCalculatorSymbolOperator.default)) {
        throw 'Error: Cannot mark node as unary operator, because symbol is not an operator but of type ' + this.symbol.constructor.name;
      }

      this.isUnaryOperator = isUnaryOperator;
    }
  }]);

  return FrontCalculatorParserNodeSymbol;
}(_frontCalculatorParserNode.default);

exports.default = FrontCalculatorParserNodeSymbol;

},{"../../symbol/abstract/front.calculator.symbol.operator.abstract":12,"./front.calculator.parser.node.abstract":5}],9:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

var FrontCalculatorSymbolAbstract = /*#__PURE__*/function () {
  function FrontCalculatorSymbolAbstract() {
    _classCallCheck(this, FrontCalculatorSymbolAbstract);

    /**
     * Array with the 1-n (exception: the Numbers class may have 0)
     * unique identifiers (the textual representation of a symbol)
     * of the symbol. Example: ['/', ':']
     * Attention: The identifiers are case-sensitive, however,
     * valid identifiers in a term are always written in lower-case.
     * Therefore identifiers always have to be written in lower-case!
     *
     * @type {String[]}
     */
    this.identifiers = [];
  }
  /**
   * Getter for the identifiers of the symbol.
   * Attention: The identifiers will be lower-cased!
   * @returns {String[]}
   */


  _createClass(FrontCalculatorSymbolAbstract, [{
    key: "getIdentifiers",
    value: function getIdentifiers() {
      var lowerIdentifiers = [];
      this.identifiers.forEach(function (identifier) {
        lowerIdentifiers.push(identifier.toLowerCase());
      });
      return lowerIdentifiers;
    }
  }]);

  return FrontCalculatorSymbolAbstract;
}();

exports.default = FrontCalculatorSymbolAbstract;

},{}],10:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbol = _interopRequireDefault(require("./front.calculator.symbol.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * This class is the base class for all symbols that are of the type "constant".
 * We recommend to use names as textual representations for this type of symbol.
 * Please take note of the fact that the precision of PHP float constants
 * (for example M_PI) is based on the "precision" directive in php.ini,
 * which defaults to 14.
 */
var FrontCalculatorSymbolConstantAbstract = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolConstantAbstract, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolConstantAbstract);

  function FrontCalculatorSymbolConstantAbstract() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolConstantAbstract);

    _this = _super.call(this);
    /**
     * This is the value of the constant. We use 0 as an example here,
     * but you are supposed to overwrite this in the concrete constant class.
     * Usually mathematical constants are not integers, however,
     * you are allowed to use an integer in this context.
     *
     * @type {number}
     */

    _this.value = 0;
    return _this;
  }

  return _createClass(FrontCalculatorSymbolConstantAbstract);
}(_frontCalculatorSymbol.default);

exports.default = FrontCalculatorSymbolConstantAbstract;

},{"./front.calculator.symbol.abstract":9}],11:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbol = _interopRequireDefault(require("./front.calculator.symbol.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * This class is the base class for all symbols that are of the type "function".
 * Typically the textual representation of a function consists of two or more letters.
 */
var FrontCalculatorSymbolFunctionAbstract = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolFunctionAbstract, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolFunctionAbstract);

  function FrontCalculatorSymbolFunctionAbstract() {
    _classCallCheck(this, FrontCalculatorSymbolFunctionAbstract);

    return _super.call(this);
  }
  /**
   * This method is called when the function is executed. A function can have 0-n parameters.
   * The implementation of this method is responsible to validate the number of arguments.
   * The $arguments array contains these arguments. If the number of arguments is improper,
   * the method has to throw a Exceptions\NumberOfArgumentsException exception.
   * The items of the $arguments array will always be of type int or float. They will never be null.
   * They keys will be integers starting at 0 and representing the positions of the arguments
   * in ascending order.
   * Overwrite this method in the concrete operator class.
   * If this class does NOT return a value of type int or float,
   * an exception will be thrown.
   *
   * @param {int[]|float[]} params
   * @returns {number}
   */


  _createClass(FrontCalculatorSymbolFunctionAbstract, [{
    key: "execute",
    value: function execute(params) {
      return 0.0;
    }
  }]);

  return FrontCalculatorSymbolFunctionAbstract;
}(_frontCalculatorSymbol.default);

exports.default = FrontCalculatorSymbolFunctionAbstract;

},{"./front.calculator.symbol.abstract":9}],12:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbol = _interopRequireDefault(require("./front.calculator.symbol.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * This class is the base class for all symbols that are of the type "(binary) operator".
 * The textual representation of an operator consists of a single char that is not a letter.
 * It is worth noting that a operator has the same power as a function with two parameters.
 * Operators are always binary. To mimic a unary operator you might want to create a function
 * that accepts one parameter.
 */
var FrontCalculatorSymbolOperatorAbstract = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolOperatorAbstract, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolOperatorAbstract);

  function FrontCalculatorSymbolOperatorAbstract() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolOperatorAbstract);

    _this = _super.call(this);
    /**
     * The operator precedence determines which operators to perform first
     * in order to evaluate a given term.
     * You are supposed to overwrite this constant in the concrete constant class.
     * Take a look at other operator classes to see the precedences of the predefined operators.
     * 0: default, > 0: higher, < 0: lower
     *
     * @type {number}
     */

    _this.precedence = 0;
    /**
     * Usually operators are binary, they operate on two operands (numbers).
     * But some can operate on one operand (number). The operand of a unary
     * operator is always positioned after the operator (=prefix notation).
     * Good example: "-1" Bad Example: "1-"
     * If you want to create a unary operator that operates on the left
     * operand, you should use a function instead. Functions with one
     * parameter execute unary operations in functional notation.
     * Notice: Operators can be unary AND binary (but this is a rare case)
     *
     * @type {boolean}
     */

    _this.operatesUnary = false;
    /**
     * Usually operators are binary, they operate on two operands (numbers).
     * Notice: Operators can be unary AND binary (but this is a rare case)
     *
     * @type {boolean}
     */

    _this.operatesBinary = true;
    return _this;
  }

  _createClass(FrontCalculatorSymbolOperatorAbstract, [{
    key: "operate",
    value: function operate(leftNumber, rightNumber) {
      return 0.0;
    }
  }]);

  return FrontCalculatorSymbolOperatorAbstract;
}(_frontCalculatorSymbol.default);

exports.default = FrontCalculatorSymbolOperatorAbstract;

},{"./front.calculator.symbol.abstract":9}],13:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbol = _interopRequireDefault(require("../abstract/front.calculator.symbol.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

var FrontCalculatorSymbolClosingBracket = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolClosingBracket, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolClosingBracket);

  function FrontCalculatorSymbolClosingBracket() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolClosingBracket);

    _this = _super.call(this);
    _this.identifiers = [')'];
    return _this;
  }

  return _createClass(FrontCalculatorSymbolClosingBracket);
}(_frontCalculatorSymbol.default);

exports.default = FrontCalculatorSymbolClosingBracket;

},{"../abstract/front.calculator.symbol.abstract":9}],14:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbol = _interopRequireDefault(require("../abstract/front.calculator.symbol.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

var FrontCalculatorSymbolOpeningBracket = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolOpeningBracket, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolOpeningBracket);

  function FrontCalculatorSymbolOpeningBracket() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolOpeningBracket);

    _this = _super.call(this);
    _this.identifiers = ['('];
    return _this;
  }

  return _createClass(FrontCalculatorSymbolOpeningBracket);
}(_frontCalculatorSymbol.default);

exports.default = FrontCalculatorSymbolOpeningBracket;

},{"../abstract/front.calculator.symbol.abstract":9}],15:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolConstant = _interopRequireDefault(require("../abstract/front.calculator.symbol.constant.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Math.PI
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/PI
 */
var FrontCalculatorSymbolConstantPi = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolConstantPi, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolConstantPi);

  function FrontCalculatorSymbolConstantPi() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolConstantPi);

    _this = _super.call(this);
    _this.identifiers = ['pi'];
    _this.value = Math.PI;
    return _this;
  }

  return _createClass(FrontCalculatorSymbolConstantPi);
}(_frontCalculatorSymbolConstant.default);

exports.default = FrontCalculatorSymbolConstantPi;

},{"../abstract/front.calculator.symbol.constant.abstract":10}],16:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbol = _interopRequireDefault(require("./front.calculator.symbol.number"));

var _frontCalculatorSymbol2 = _interopRequireDefault(require("./front.calculator.symbol.separator"));

var _frontCalculatorSymbolOpening = _interopRequireDefault(require("./brackets/front.calculator.symbol.opening.bracket"));

var _frontCalculatorSymbolClosing = _interopRequireDefault(require("./brackets/front.calculator.symbol.closing.bracket"));

var _frontCalculatorSymbolConstant = _interopRequireDefault(require("./constants/front.calculator.symbol.constant.pi"));

var _frontCalculatorSymbolOperator = _interopRequireDefault(require("./operators/front.calculator.symbol.operator.addition"));

var _frontCalculatorSymbolOperator2 = _interopRequireDefault(require("./operators/front.calculator.symbol.operator.division"));

var _frontCalculatorSymbolOperator3 = _interopRequireDefault(require("./operators/front.calculator.symbol.operator.exponentiation"));

var _frontCalculatorSymbolOperator4 = _interopRequireDefault(require("./operators/front.calculator.symbol.operator.modulo"));

var _frontCalculatorSymbolOperator5 = _interopRequireDefault(require("./operators/front.calculator.symbol.operator.multiplication"));

var _frontCalculatorSymbolOperator6 = _interopRequireDefault(require("./operators/front.calculator.symbol.operator.subtraction"));

var _frontCalculatorSymbolFunction = _interopRequireDefault(require("./functions/front.calculator.symbol.function.abs"));

var _frontCalculatorSymbolFunction2 = _interopRequireDefault(require("./functions/front.calculator.symbol.function.avg"));

var _frontCalculatorSymbolFunction3 = _interopRequireDefault(require("./functions/front.calculator.symbol.function.ceil"));

var _frontCalculatorSymbolFunction4 = _interopRequireDefault(require("./functions/front.calculator.symbol.function.floor"));

var _frontCalculatorSymbolFunction5 = _interopRequireDefault(require("./functions/front.calculator.symbol.function.max"));

var _frontCalculatorSymbolFunction6 = _interopRequireDefault(require("./functions/front.calculator.symbol.function.min"));

var _frontCalculatorSymbolFunction7 = _interopRequireDefault(require("./functions/front.calculator.symbol.function.round"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

var FrontCalculatorSymbolLoader = /*#__PURE__*/function () {
  function FrontCalculatorSymbolLoader() {
    _classCallCheck(this, FrontCalculatorSymbolLoader);

    /**
     *
     * @type {{FrontCalculatorSymbolOperatorModulo: FrontCalculatorSymbolOperatorModulo, FrontCalculatorSymbolOperatorSubtraction: FrontCalculatorSymbolOperatorSubtraction, FrontCalculatorSymbolOperatorExponentiation: FrontCalculatorSymbolOperatorExponentiation, FrontCalculatorSymbolOperatorAddition: FrontCalculatorSymbolOperatorAddition, FrontCalculatorSymbolClosingBracket: FrontCalculatorSymbolClosingBracket, FrontCalculatorSymbolFunctionMax: FrontCalculatorSymbolFunctionMax, FrontCalculatorSymbolFunctionCeil: FrontCalculatorSymbolFunctionCeil, FrontCalculatorSymbolSeparator: FrontCalculatorSymbolSeparator, FrontCalculatorSymbolOperatorMultiplication: FrontCalculatorSymbolOperatorMultiplication, FrontCalculatorSymbolFunctionAbs: FrontCalculatorSymbolFunctionAbs, FrontCalculatorSymbolFunctionAvg: FrontCalculatorSymbolFunctionAvg, FrontCalculatorSymbolFunctionFloor: FrontCalculatorSymbolFunctionFloor, FrontCalculatorSymbolFunctionMin: FrontCalculatorSymbolFunctionMin, FrontCalculatorSymbolOperatorDivision: FrontCalculatorSymbolOperatorDivision, FrontCalculatorSymbolNumber: FrontCalculatorSymbolNumber, FrontCalculatorSymbolOpeningBracket: FrontCalculatorSymbolOpeningBracket, FrontCalculatorSymbolConstantPi: FrontCalculatorSymbolConstantPi, FrontCalculatorSymbolFunctionRound: FrontCalculatorSymbolFunctionRound}}
     */
    this.symbols = {
      FrontCalculatorSymbolNumber: new _frontCalculatorSymbol.default(),
      FrontCalculatorSymbolSeparator: new _frontCalculatorSymbol2.default(),
      FrontCalculatorSymbolOpeningBracket: new _frontCalculatorSymbolOpening.default(),
      FrontCalculatorSymbolClosingBracket: new _frontCalculatorSymbolClosing.default(),
      FrontCalculatorSymbolConstantPi: new _frontCalculatorSymbolConstant.default(),
      FrontCalculatorSymbolOperatorAddition: new _frontCalculatorSymbolOperator.default(),
      FrontCalculatorSymbolOperatorDivision: new _frontCalculatorSymbolOperator2.default(),
      FrontCalculatorSymbolOperatorExponentiation: new _frontCalculatorSymbolOperator3.default(),
      FrontCalculatorSymbolOperatorModulo: new _frontCalculatorSymbolOperator4.default(),
      FrontCalculatorSymbolOperatorMultiplication: new _frontCalculatorSymbolOperator5.default(),
      FrontCalculatorSymbolOperatorSubtraction: new _frontCalculatorSymbolOperator6.default(),
      FrontCalculatorSymbolFunctionAbs: new _frontCalculatorSymbolFunction.default(),
      FrontCalculatorSymbolFunctionAvg: new _frontCalculatorSymbolFunction2.default(),
      FrontCalculatorSymbolFunctionCeil: new _frontCalculatorSymbolFunction3.default(),
      FrontCalculatorSymbolFunctionFloor: new _frontCalculatorSymbolFunction4.default(),
      FrontCalculatorSymbolFunctionMax: new _frontCalculatorSymbolFunction5.default(),
      FrontCalculatorSymbolFunctionMin: new _frontCalculatorSymbolFunction6.default(),
      FrontCalculatorSymbolFunctionRound: new _frontCalculatorSymbolFunction7.default()
    };
  }
  /**
   * Returns the symbol that has the given identifier.
   * Returns null if none is found.
   *
   * @param identifier
   * @returns {FrontCalculatorSymbolAbstract|null}
   */


  _createClass(FrontCalculatorSymbolLoader, [{
    key: "find",
    value: function find(identifier) {
      identifier = identifier.toLowerCase();

      for (var key in this.symbols) {
        if (this.symbols.hasOwnProperty(key)) {
          var symbol = this.symbols[key];

          if (symbol.getIdentifiers().indexOf(identifier) >= 0) {
            return symbol;
          }
        }
      }

      return null;
    }
    /**
     * Returns all symbols that inherit from a given abstract
     * parent type (class): The parent type has to be an
     * AbstractSymbol.
     * Notice: The parent type name will not be validated!
     *
     * @param parentTypeName
     * @returns {FrontCalculatorSymbolAbstract[]}
     */

  }, {
    key: "findSubTypes",
    value: function findSubTypes(parentTypeName) {
      var symbols = [];

      for (var key in this.symbols) {
        if (this.symbols.hasOwnProperty(key)) {
          var symbol = this.symbols[key];

          if (symbol instanceof parentTypeName) {
            symbols.push(symbol);
          }
        }
      }

      return symbols;
    }
  }]);

  return FrontCalculatorSymbolLoader;
}();

exports.default = FrontCalculatorSymbolLoader;

},{"./brackets/front.calculator.symbol.closing.bracket":13,"./brackets/front.calculator.symbol.opening.bracket":14,"./constants/front.calculator.symbol.constant.pi":15,"./front.calculator.symbol.number":17,"./front.calculator.symbol.separator":18,"./functions/front.calculator.symbol.function.abs":19,"./functions/front.calculator.symbol.function.avg":20,"./functions/front.calculator.symbol.function.ceil":21,"./functions/front.calculator.symbol.function.floor":22,"./functions/front.calculator.symbol.function.max":23,"./functions/front.calculator.symbol.function.min":24,"./functions/front.calculator.symbol.function.round":25,"./operators/front.calculator.symbol.operator.addition":26,"./operators/front.calculator.symbol.operator.division":27,"./operators/front.calculator.symbol.operator.exponentiation":28,"./operators/front.calculator.symbol.operator.modulo":29,"./operators/front.calculator.symbol.operator.multiplication":30,"./operators/front.calculator.symbol.operator.subtraction":31}],17:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbol = _interopRequireDefault(require("./abstract/front.calculator.symbol.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * This class is the class that represents symbols of type "number".
 * Numbers are completely handled by the tokenizer/parser so there is no need to
 * create more than this concrete, empty number class that does not specify
 * a textual representation of numbers (numbers always consist of digits
 * and may include a single dot).
 */
var FrontCalculatorSymbolNumber = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolNumber, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolNumber);

  function FrontCalculatorSymbolNumber() {
    _classCallCheck(this, FrontCalculatorSymbolNumber);

    return _super.call(this);
  }

  return _createClass(FrontCalculatorSymbolNumber);
}(_frontCalculatorSymbol.default);

exports.default = FrontCalculatorSymbolNumber;

},{"./abstract/front.calculator.symbol.abstract":9}],18:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbol = _interopRequireDefault(require("./abstract/front.calculator.symbol.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * This class is a class that represents symbols of type "separator".
 * A separator separates the arguments of a (mathematical) function.
 * Most likely we will only need one concrete "separator" class.
 */
var FrontCalculatorSymbolSeparator = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolSeparator, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolSeparator);

  function FrontCalculatorSymbolSeparator() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolSeparator);

    _this = _super.call(this);
    _this.identifiers = [','];
    return _this;
  }

  return _createClass(FrontCalculatorSymbolSeparator);
}(_frontCalculatorSymbol.default);

exports.default = FrontCalculatorSymbolSeparator;

},{"./abstract/front.calculator.symbol.abstract":9}],19:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolFunction = _interopRequireDefault(require("../abstract/front.calculator.symbol.function.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Math.abs() function. Expects one parameter.
 * Example: "abs(2)" => 2, "abs(-2)" => 2, "abs(0)" => 0
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/abs
 */
var FrontCalculatorSymbolFunctionAbs = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolFunctionAbs, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolFunctionAbs);

  function FrontCalculatorSymbolFunctionAbs() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolFunctionAbs);

    _this = _super.call(this);
    _this.identifiers = ['abs'];
    return _this;
  }

  _createClass(FrontCalculatorSymbolFunctionAbs, [{
    key: "execute",
    value: function execute(params) {
      if (params.length !== 1) {
        throw 'Error: Expected one argument, got ' + params.length;
      }

      var number = params[0];
      return Math.abs(number);
    }
  }]);

  return FrontCalculatorSymbolFunctionAbs;
}(_frontCalculatorSymbolFunction.default);

exports.default = FrontCalculatorSymbolFunctionAbs;

},{"../abstract/front.calculator.symbol.function.abstract":11}],20:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolFunction = _interopRequireDefault(require("../abstract/front.calculator.symbol.function.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Math.abs() function. Expects one parameter.
 * Example: "abs(2)" => 2, "abs(-2)" => 2, "abs(0)" => 0
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/abs
 */
var FrontCalculatorSymbolFunctionAvg = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolFunctionAvg, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolFunctionAvg);

  function FrontCalculatorSymbolFunctionAvg() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolFunctionAvg);

    _this = _super.call(this);
    _this.identifiers = ['avg'];
    return _this;
  }

  _createClass(FrontCalculatorSymbolFunctionAvg, [{
    key: "execute",
    value: function execute(params) {
      if (params.length < 1) {
        throw 'Error: Expected at least one argument, got ' + params.length;
      }

      var sum = 0.0;

      for (var i = 0; i < params.length; i++) {
        sum += params[i];
      }

      return sum / params.length;
    }
  }]);

  return FrontCalculatorSymbolFunctionAvg;
}(_frontCalculatorSymbolFunction.default);

exports.default = FrontCalculatorSymbolFunctionAvg;

},{"../abstract/front.calculator.symbol.function.abstract":11}],21:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolFunction = _interopRequireDefault(require("../abstract/front.calculator.symbol.function.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Math.ceil() function aka round fractions up.
 * Expects one parameter.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/ceil
 */
var FrontCalculatorSymbolFunctionCeil = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolFunctionCeil, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolFunctionCeil);

  function FrontCalculatorSymbolFunctionCeil() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolFunctionCeil);

    _this = _super.call(this);
    _this.identifiers = ['ceil'];
    return _this;
  }

  _createClass(FrontCalculatorSymbolFunctionCeil, [{
    key: "execute",
    value: function execute(params) {
      if (params.length !== 1) {
        throw 'Error: Expected one argument, got ' + params.length;
      }

      return Math.ceil(params[0]);
    }
  }]);

  return FrontCalculatorSymbolFunctionCeil;
}(_frontCalculatorSymbolFunction.default);

exports.default = FrontCalculatorSymbolFunctionCeil;

},{"../abstract/front.calculator.symbol.function.abstract":11}],22:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolFunction = _interopRequireDefault(require("../abstract/front.calculator.symbol.function.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Math.floor() function aka round fractions down.
 * Expects one parameter.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/floor
 */
var FrontCalculatorSymbolFunctionFloor = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolFunctionFloor, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolFunctionFloor);

  function FrontCalculatorSymbolFunctionFloor() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolFunctionFloor);

    _this = _super.call(this);
    _this.identifiers = ['floor'];
    return _this;
  }

  _createClass(FrontCalculatorSymbolFunctionFloor, [{
    key: "execute",
    value: function execute(params) {
      if (params.length !== 1) {
        throw 'Error: Expected one argument, got ' + params.length;
      }

      return Math.floor(params[0]);
    }
  }]);

  return FrontCalculatorSymbolFunctionFloor;
}(_frontCalculatorSymbolFunction.default);

exports.default = FrontCalculatorSymbolFunctionFloor;

},{"../abstract/front.calculator.symbol.function.abstract":11}],23:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolFunction = _interopRequireDefault(require("../abstract/front.calculator.symbol.function.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Math.max() function. Expects at least one parameter.
 * Example: "max(1,2,3)" => 3, "max(1,-1)" => 1, "max(0,0)" => 0, "max(2)" => 2
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/max
 */
var FrontCalculatorSymbolFunctionMax = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolFunctionMax, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolFunctionMax);

  function FrontCalculatorSymbolFunctionMax() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolFunctionMax);

    _this = _super.call(this);
    _this.identifiers = ['max'];
    return _this;
  }

  _createClass(FrontCalculatorSymbolFunctionMax, [{
    key: "execute",
    value: function execute(params) {
      if (params.length < 1) {
        throw 'Error: Expected at least one argument, got ' + params.length;
      }

      return Math.max.apply(Math, _toConsumableArray(params));
    }
  }]);

  return FrontCalculatorSymbolFunctionMax;
}(_frontCalculatorSymbolFunction.default);

exports.default = FrontCalculatorSymbolFunctionMax;

},{"../abstract/front.calculator.symbol.function.abstract":11}],24:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolFunction = _interopRequireDefault(require("../abstract/front.calculator.symbol.function.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Math.min() function. Expects at least one parameter.
 * Example: "min(1,2,3)" => 1, "min(1,-1)" => -1, "min(0,0)" => 0, "min(2)" => 2
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/min
 */
var FrontCalculatorSymbolFunctionMin = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolFunctionMin, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolFunctionMin);

  function FrontCalculatorSymbolFunctionMin() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolFunctionMin);

    _this = _super.call(this);
    _this.identifiers = ['min'];
    return _this;
  }

  _createClass(FrontCalculatorSymbolFunctionMin, [{
    key: "execute",
    value: function execute(params) {
      if (params.length < 1) {
        throw 'Error: Expected at least one argument, got ' + params.length;
      }

      return Math.min.apply(Math, _toConsumableArray(params));
    }
  }]);

  return FrontCalculatorSymbolFunctionMin;
}(_frontCalculatorSymbolFunction.default);

exports.default = FrontCalculatorSymbolFunctionMin;

},{"../abstract/front.calculator.symbol.function.abstract":11}],25:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolFunction = _interopRequireDefault(require("../abstract/front.calculator.symbol.function.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Math.round() function aka rounds a float.
 * Expects one parameter.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/round
 */
var FrontCalculatorSymbolFunctionRound = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolFunctionRound, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolFunctionRound);

  function FrontCalculatorSymbolFunctionRound() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolFunctionRound);

    _this = _super.call(this);
    _this.identifiers = ['round'];
    return _this;
  }

  _createClass(FrontCalculatorSymbolFunctionRound, [{
    key: "execute",
    value: function execute(params) {
      if (params.length !== 1) {
        throw 'Error: Expected one argument, got ' + params.length;
      }

      return Math.round(params[0]);
    }
  }]);

  return FrontCalculatorSymbolFunctionRound;
}(_frontCalculatorSymbolFunction.default);

exports.default = FrontCalculatorSymbolFunctionRound;

},{"../abstract/front.calculator.symbol.function.abstract":11}],26:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolOperator = _interopRequireDefault(require("../abstract/front.calculator.symbol.operator.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Operator for mathematical addition.
 * Example: "1+2" => 3
 *
 * @see     https://en.wikipedia.org/wiki/Addition
 *
 */
var FrontCalculatorSymbolOperatorAddition = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolOperatorAddition, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolOperatorAddition);

  function FrontCalculatorSymbolOperatorAddition() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolOperatorAddition);

    _this = _super.call(this);
    _this.identifiers = ['+'];
    _this.precedence = 100;
    return _this;
  }

  _createClass(FrontCalculatorSymbolOperatorAddition, [{
    key: "operate",
    value: function operate(leftNumber, rightNumber) {
      return leftNumber + rightNumber;
    }
  }]);

  return FrontCalculatorSymbolOperatorAddition;
}(_frontCalculatorSymbolOperator.default);

exports.default = FrontCalculatorSymbolOperatorAddition;

},{"../abstract/front.calculator.symbol.operator.abstract":12}],27:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolOperator = _interopRequireDefault(require("../abstract/front.calculator.symbol.operator.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Operator for mathematical division.
 * Example: "6/2" => 3, "6/0" => PHP warning
 *
 * @see     https://en.wikipedia.org/wiki/Division_(mathematics)
 *
 */
var FrontCalculatorSymbolOperatorDivision = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolOperatorDivision, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolOperatorDivision);

  function FrontCalculatorSymbolOperatorDivision() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolOperatorDivision);

    _this = _super.call(this);
    _this.identifiers = ['/'];
    _this.precedence = 200;
    return _this;
  }

  _createClass(FrontCalculatorSymbolOperatorDivision, [{
    key: "operate",
    value: function operate(leftNumber, rightNumber) {
      var result = leftNumber / rightNumber; // // force to 0
      // if (!isFinite(result)) {
      // 	return 0;
      // }

      return result;
    }
  }]);

  return FrontCalculatorSymbolOperatorDivision;
}(_frontCalculatorSymbolOperator.default);

exports.default = FrontCalculatorSymbolOperatorDivision;

},{"../abstract/front.calculator.symbol.operator.abstract":12}],28:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolOperator = _interopRequireDefault(require("../abstract/front.calculator.symbol.operator.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Operator for mathematical exponentiation.
 * Example: "3^2" => 9, "-3^2" => -9, "3^-2" equals "3^(-2)"
 *
 * @see     https://en.wikipedia.org/wiki/Exponentiation
 * @see     https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/pow
 *
 */
var FrontCalculatorSymbolOperatorExponentiation = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolOperatorExponentiation, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolOperatorExponentiation);

  function FrontCalculatorSymbolOperatorExponentiation() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolOperatorExponentiation);

    _this = _super.call(this);
    _this.identifiers = ['^'];
    _this.precedence = 300;
    return _this;
  }

  _createClass(FrontCalculatorSymbolOperatorExponentiation, [{
    key: "operate",
    value: function operate(leftNumber, rightNumber) {
      return Math.pow(leftNumber, rightNumber);
    }
  }]);

  return FrontCalculatorSymbolOperatorExponentiation;
}(_frontCalculatorSymbolOperator.default);

exports.default = FrontCalculatorSymbolOperatorExponentiation;

},{"../abstract/front.calculator.symbol.operator.abstract":12}],29:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolOperator = _interopRequireDefault(require("../abstract/front.calculator.symbol.operator.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Operator for mathematical modulo operation.
 * Example: "5%3" => 2
 *
 * @see https://en.wikipedia.org/wiki/Modulo_operation
 *
 */
var FrontCalculatorSymbolOperatorModulo = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolOperatorModulo, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolOperatorModulo);

  function FrontCalculatorSymbolOperatorModulo() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolOperatorModulo);

    _this = _super.call(this);
    _this.identifiers = ['%'];
    _this.precedence = 200;
    return _this;
  }

  _createClass(FrontCalculatorSymbolOperatorModulo, [{
    key: "operate",
    value: function operate(leftNumber, rightNumber) {
      return leftNumber % rightNumber;
    }
  }]);

  return FrontCalculatorSymbolOperatorModulo;
}(_frontCalculatorSymbolOperator.default);

exports.default = FrontCalculatorSymbolOperatorModulo;

},{"../abstract/front.calculator.symbol.operator.abstract":12}],30:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolOperator = _interopRequireDefault(require("../abstract/front.calculator.symbol.operator.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Operator for mathematical multiplication.
 * Example: "2*3" => 6
 *
 * @see     https://en.wikipedia.org/wiki/Multiplication
 *
 */
var FrontCalculatorSymbolOperatorMultiplication = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolOperatorMultiplication, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolOperatorMultiplication);

  function FrontCalculatorSymbolOperatorMultiplication() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolOperatorMultiplication);

    _this = _super.call(this);
    _this.identifiers = ['*'];
    _this.precedence = 200;
    return _this;
  }

  _createClass(FrontCalculatorSymbolOperatorMultiplication, [{
    key: "operate",
    value: function operate(leftNumber, rightNumber) {
      return leftNumber * rightNumber;
    }
  }]);

  return FrontCalculatorSymbolOperatorMultiplication;
}(_frontCalculatorSymbolOperator.default);

exports.default = FrontCalculatorSymbolOperatorMultiplication;

},{"../abstract/front.calculator.symbol.operator.abstract":12}],31:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _frontCalculatorSymbolOperator = _interopRequireDefault(require("../abstract/front.calculator.symbol.operator.abstract"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Operator for mathematical subtraction.
 * Example: "2-1" => 1
 *
 * @see     https://en.wikipedia.org/wiki/Subtraction
 *
 */
var FrontCalculatorSymbolOperatorSubtraction = /*#__PURE__*/function (_FrontCalculatorSymbo) {
  _inherits(FrontCalculatorSymbolOperatorSubtraction, _FrontCalculatorSymbo);

  var _super = _createSuper(FrontCalculatorSymbolOperatorSubtraction);

  function FrontCalculatorSymbolOperatorSubtraction() {
    var _this;

    _classCallCheck(this, FrontCalculatorSymbolOperatorSubtraction);

    _this = _super.call(this);
    _this.identifiers = ['-'];
    _this.precedence = 100;
    /**
     * Notice: The subtraction operator is unary AND binary!
     *
     * @type {boolean}
     */

    _this.operatesUnary = true;
    return _this;
  }

  _createClass(FrontCalculatorSymbolOperatorSubtraction, [{
    key: "operate",
    value: function operate(leftNumber, rightNumber) {
      return leftNumber - rightNumber;
    }
  }]);

  return FrontCalculatorSymbolOperatorSubtraction;
}(_frontCalculatorSymbolOperator.default);

exports.default = FrontCalculatorSymbolOperatorSubtraction;

},{"../abstract/front.calculator.symbol.operator.abstract":12}]},{},[1]);

/*!
 * https://github.com/alfaslash/array-includes/blob/master/array-includes.js
 *
 * Array includes 1.0.4
 * https://github.com/alfaslash/array-includes
 *
 * Released under the Apache License 2.0
 * https://github.com/alfaslash/array-includes/blob/master/LICENSE
 */
if (![].includes) {
    Array.prototype.includes = function (searchElement, fromIndex) {
        'use strict';
        var O = Object(this);
        var len = parseInt(O.length) || 0;
        if (len === 0) {
            return false;
        }
        var n = parseInt(fromIndex) || 0;
        var k;
        if (n >= 0) {
            k = n;
        } else {
            k = len + n;
            if (k < 0) {
                k = 0;
            }
        }
        while (k < len) {
            var currentElement = O[k];
            if (searchElement === currentElement ||
                (searchElement !== searchElement && currentElement !== currentElement)
            ) {
                return true;
            }
            k++;
        }
        return false;
    };
}

// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "forminatorLoader",
	    defaults   = {
		    action: '',
		    type: '',
		    id: '',
		    render_id: '',
		    is_preview: '',
		    preview_data: [],
			 nonce: false,
		    last_submit_data: {},
		    extra: {},
	    };

	// The actual plugin constructor
	function ForminatorLoader(element, options) {
		this.element = element;
		this.$el     = $(this.element);

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings  = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name     = pluginName;

		this.frontInitCalled = false;
		this.scriptsQue      = [];
		this.frontOptions    = null;
		this.leadFrontOptions    = null;

		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorLoader.prototype, {
		init: function () {
			var param = (decodeURI(document.location.search)).replace(/(^\?)/, '').split("&").map(function (n) {
				return n = n.split("="), this[n[0]] = n[1], this
			}.bind({}))[0];

			param.action           = this.settings.action;
			param.type             = this.settings.type;
			param.id               = this.settings.id;
			param.render_id        = this.settings.render_id;
			param.is_preview       = this.settings.is_preview;
			param.preview_data     = JSON.stringify(this.settings.preview_data);
			param.last_submit_data = this.settings.last_submit_data;
			param.extra            = this.settings.extra;
			param.nonce				  = this.settings.nonce;

			if ( 'undefined' !== typeof this.settings.has_lead ) {
				param.has_lead         = this.settings.has_lead;
				param.leads_id         = this.settings.leads_id;
			}

			this.load_ajax(param);

		},
		load_ajax: function (param) {
			var self = this;
			$.ajax({
					type: 'POST',
					url: window.ForminatorFront.ajaxUrl,
					data: param,
					cache: false,
					beforeSend: function () {
						$(document).trigger('before.load.forminator', param.id);
					},
					success: function (data) {
						if (data.success) {
							var response = data.data;

							$(document).trigger('response.success.load.forminator', param.id, data);

							if (!response.is_ajax_load) {
								//not load ajax
								return false;
							}

							var pagination_config = [];

							if(typeof response.pagination_config === "undefined" && typeof response.options.pagination_config !== "undefined") {
								pagination_config = response.options.pagination_config;
							}

							// response.pagination_config
							if (pagination_config) {
								window.Forminator_Cform_Paginations           = window.Forminator_Cform_Paginations || [];
								window.Forminator_Cform_Paginations[param.id] = pagination_config;
							}

							self.frontOptions = response.options || null;

							// Solution for form Preview
							if (typeof window.Forminator_Cform_Paginations === "undefined" && self.frontOptions.pagination_config) {
								window.Forminator_Cform_Paginations           = window.Forminator_Cform_Paginations || [];
								window.Forminator_Cform_Paginations[param.id] = self.frontOptions.pagination_config;
							}

							if( 'undefined' !== typeof response.lead_options ) {

								self.leadFrontOptions = response.lead_options || null;

								if (typeof window.Forminator_Cform_Paginations === "undefined" && self.leadFrontOptions.pagination_config) {
									window.Forminator_Cform_Paginations           = window.Forminator_Cform_Paginations || [];
									window.Forminator_Cform_Paginations[param.leads_id] = self.leadFrontOptions.pagination_config;
								}

							}

							//response.html
							if (response.html) {
								var style  = response.style || null;
								var script = response.script || null;
								self.render_html(response.html, style, script);
							}

							//response.styles
							if (response.styles) {
								self.maybe_append_styles(response.styles);
							}

							if (response.scripts) {
								self.maybe_append_scripts(response.scripts);
							}

							if (!response.scripts && self.frontOptions) {
								// when no additional scripts, direct execute
								self.init_front();
							}


						} else {
							$(document).trigger('response.error.load.forminator', param.id, data);
						}

					},
					error: function () {
						$(document).trigger('request.error.load.forminator', param.id);
					},
				}
			).always(function () {
				$(document).trigger('after.load.forminator', param.id);
			});
		},

		render_html: function (html, style, script) {
			var id              = this.settings.id,
			    render_id       = this.settings.render_id,
			    // save message
			    message         = '',
			    wrapper_message = null;

			wrapper_message = this.$el.find('.forminator-response-message');
			if (wrapper_message.length) {
				message = wrapper_message.get(0).outerHTML;
			}
			wrapper_message = this.$el.find('.forminator-poll-response-message');
			if (wrapper_message.length) {
				message = wrapper_message.get(0).outerHTML;
			}

			if ( this.$el.parent().hasClass( 'forminator-guttenberg' ) ) {
				this.$el.parent()
				    .html(html);
			} else {
				this.$el
			    .replaceWith(html);
			}

			if (message) {
				$('#forminator-module-' + id + '[data-forminator-render=' + render_id + '] .forminator-response-message')
					.replaceWith(message);
				$('#forminator-module-' + id + '[data-forminator-render=' + render_id + '] .forminator-poll-response-message')
					.replaceWith(message);
			}

			//response.style
			if (style) {
				if ($('style#forminator-module-' + id).length) {
					$('style#forminator-module-' + id).remove();
				}
				$('body').append(style);
			}

			if (script) {
				$('body').append(script);

			}
		},

		maybe_append_styles: function (styles) {
			for (var style_id in styles) {
				if (styles.hasOwnProperty(style_id)) {
					// already loaded?
					if (!$('link#' + style_id).length) {
						var link = $('<link>');
						link.attr('rel', 'stylesheet');
						link.attr('id', style_id);
						link.attr('type', 'text/css');
						link.attr('media', 'all');
						link.attr('href', styles[style_id].src);
						$('head').append(link);
					}
				}
			}
		},

		maybe_append_scripts: function (scripts) {
			var self           = this,
				scripts_to_load = [],
				hasHustle       = $( 'body' ).find( '.hustle-ui' ).length,
            paypal_src      = $( 'body' ).find( "script[src^='https://www.paypal.com/sdk/js']" ).attr('src')
			;

			for (var script_id in scripts) {
				if (scripts.hasOwnProperty(script_id)) {
					var load_on = scripts[script_id].on;
					var load_of = scripts[script_id].load;
					// already loaded?
					if ('window' === load_on) {
						if ( window[load_of] && 'forminator-google-recaptcha' !== script_id && 0 === hasHustle ) {
							continue;
						}
					} else if ('$' === load_on) {
						if ($.fn[load_of]) {
							continue;
						}
					}

					var script = {};
					script.src = scripts[script_id].src;
                    // Check if a paypal script is already loaded.
                    if ( script.src !== paypal_src ) {
                        scripts_to_load.push(script);
                        this.scriptsQue.push(script_id);
                    }
				}
			}


			if (!this.scriptsQue.length) {
				this.init_front();
				return;
			}

			for (var script_id_to_load in scripts_to_load) {
				if (scripts_to_load.hasOwnProperty(script_id_to_load)) {
					this.load_script(scripts_to_load[script_id_to_load]);
				}
			}

		},

		load_script: function (script_props) {
			var self   = this;
			var script = document.createElement('script');
			var body   = document.getElementsByTagName('body')[0];

			script.type   = 'text/javascript';
			script.src    = script_props.src;
			script.async  = true;
			script.defer  = true;
			script.onload = function () {
				self.script_on_load();
			};

			// Check if script is already loaded or not.
			if ( 0 === $( 'script[src="' + script.src + '"]' ).length ) {
				body.appendChild(script);
			} else {
				self.script_on_load();
			}
		},

		script_on_load: function () {
			this.scriptsQue.pop();

			if (!this.scriptsQue.length) {
				this.init_front();
			}
		},

		init_front: function () {
			if (this.frontInitCalled) {
				return;
			}

			this.frontInitCalled = true;
			var id               = this.settings.id;
			var render_id        = this.settings.render_id;
			var options          = this.frontOptions || null;
			var lead_options     = this.leadFrontOptions || null;

			if (options) {
				$('#forminator-module-' + id + '[data-forminator-render="' + render_id + '"]')
					.forminatorFront(options);
			}
			if ( 'undefined' !== typeof this.settings.has_lead && lead_options) {
				var leads_id = this.settings.leads_id;
				$('#forminator-module-' + leads_id + '[data-forminator-render="' + render_id + '"]')
					.forminatorFront(lead_options);
			}

			this.init_window_vars();

		},

		init_window_vars: function () {
			// RELOAD type
			if (typeof ForminatorValidationErrors !== 'undefined') {
				var forminatorFrontSubmit = jQuery(ForminatorValidationErrors.selector).data('forminatorFrontSubmit');
				if (typeof forminatorFrontSubmit !== 'undefined') {
					forminatorFrontSubmit.show_messages(ForminatorValidationErrors.errors);
				}
			}

			if (typeof ForminatorFormHider !== 'undefined') {
				var forminatorFront = jQuery(ForminatorFormHider.selector).data('forminatorFront');
				if (typeof forminatorFront !== 'undefined') {
					forminatorFront.hide();
				}
			}
		}
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorLoader(this, options));
			}
		});
	};


})(jQuery, window, document);

// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "forminatorFront",
	    defaults   = {
		    form_type: 'custom-form',
		    rules: {},
		    messages: {},
		    conditions: {},
		    inline_validation: false,
		    print_value: false,
		    chart_design: 'bar',
		    chart_options: {},
		    forminator_fields: [],
		    general_messages: {
			    calculation_error: 'Failed to calculate field.',
			    payment_require_ssl_error: 'SSL required to submit this form, please check your URL.',
				payment_require_amount_error: 'PayPal amount must be greater than 0.',
			    form_has_error: 'Please correct the errors before submission.'
		    },
		    payment_require_ssl : false,
	    };

	// The actual plugin constructor
	function ForminatorFront(element, options) {
		this.element                    = element;
		this.$el                        = $(this.element);
		this.forminator_selector        = '#' + $(this.element).attr('id') + '[data-forminator-render="' + $(this.element).data('forminator-render') + '"]';
		this.forminator_loader_selector = 'div[data-forminator-render="' + $(this.element).data('forminator-render') + '"]' + '[data-form="' + $(this.element).attr('id') + '"]';

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings = $.extend({}, defaults, options);

		// special treatment for rules, messages, and conditions
		if (typeof this.settings.messages !== 'undefined') {
			this.settings.messages = this.maybeParseStringToJson(this.settings.messages, 'object');
		}
		if (typeof this.settings.rules !== 'undefined') {
			this.settings.rules = this.maybeParseStringToJson(this.settings.rules, 'object');
		}
		if (typeof this.settings.calendar !== 'undefined') {
			this.settings.calendar = this.maybeParseStringToJson(this.settings.calendar, 'array');
		}

		this._defaults = defaults;
		this._name     = pluginName;
		this.form_id   = 0;
		this.template_type = '';

		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFront.prototype, {
		init: function () {
			var self = this;

			if (this.$el.find('input[name="form_id"]').length > 0) {
				this.form_id = this.$el.find('input[name="form_id"]').val();
			}
			if (this.$el.find('input[name="form_type"]').length > 0) {
				this.template_type = this.$el.find('input[name="form_type"]').val();
			}

			$(this.forminator_loader_selector).remove();

			// If form from hustle popup, do not show
			if (this.$el.closest('.wph-modal').length === 0) {
				this.$el.show();
			}

			// Show form when popup trigger with click
			$(document).on("hustle:module:displayed", function (e, data) {
				var $modal = $('.wph-modal-active');
				$modal.find('form').css('display', '');
			});

			// Show form when popup trigger
			setTimeout(function () {
				var $modal = $('.wph-modal-active');
				$modal.find('form').css('display', '');
			}, 10);

			//selective activation based on type of form
			switch (this.settings.form_type) {
				case  'custom-form':
					$( this.element ).each( function() {
						self.init_custom_form( this );
					});

					break;
				case  'poll':
					this.init_poll_form();
					break;
				case  'quiz':
					this.init_quiz_form();
					break;

			}

			//init submit
			var submitOptions = {
				form_type: self.settings.form_type,
				forminator_selector: self.forminator_selector,
				chart_design: self.settings.chart_design,
				chart_options: self.settings.chart_options,
				has_quiz_loader: self.settings.has_quiz_loader,
				has_loader: self.settings.has_loader,
				loader_label: self.settings.loader_label,
				resetEnabled: self.settings.is_reset_enabled,
				inline_validation: self.settings.inline_validation,
			};

			if( 'leads' === this.template_type || 'quiz' === this.settings.form_type ) {
				submitOptions.form_placement = self.settings.form_placement;
				submitOptions.hasLeads = self.settings.hasLeads;
				submitOptions.leads_id = self.settings.leads_id;
				submitOptions.quiz_id = self.settings.quiz_id;
				submitOptions.skip_form = self.settings.skip_form;
			}

			$(this.element).forminatorFrontSubmit( submitOptions );


			// TODO: confirm usage on form type
			// Handle field activation classes
			this.activate_field();
			// Handle special classes for material design
			// this.material_field();

			// Init small form for all type of form
			this.small_form();

		},
		init_custom_form: function ( form_selector ) {

			var self 			= this,
				$saveDraft 		= this.$el.find( '.forminator-save-draft-link' ),
				saveDraftExists = 0 !== $saveDraft.length ? true : false,
				draftTimer
				;

			//initiate validator
			this.init_intlTelInput_validation( form_selector );

			if (this.settings.inline_validation) {

				$( form_selector ).forminatorFrontValidate({
					rules: self.settings.rules,
					messages: self.settings.messages
				});
			}

			// initiate calculator
			$( form_selector ).forminatorFrontCalculate({
				forminatorFields: self.settings.forminator_fields,
				generalMessages: self.settings.general_messages,
				memoizeTime: self.settings.calcs_memoize_time || 300,
			});

			// initiate merge tags
			$( form_selector ).forminatorFrontMergeTags({
				forminatorFields: self.settings.forminator_fields,
				print_value: self.settings.print_value,
			});

			//initiate pagination
			this.init_pagination( form_selector );

			// initiate payment if exist
			var first_payment = $( form_selector ).find('div[data-is-payment="true"], input[data-is-payment="true"]').first();

			if( self.settings.has_stripe ) {
				var stripe_payment = $(this.element).find('.forminator-stripe-element').first();

				this.renderStripe( self, stripe_payment );
			}

			if( self.settings.has_paypal ) {
				$(this.element).forminatorFrontPayPal({
					type: 'paypal',
					paymentEl: this.settings.paypal_config,
					paymentRequireSsl: self.settings.payment_require_ssl,
					generalMessages: self.settings.general_messages,
					has_loader: self.settings.has_loader,
					loader_label: self.settings.loader_label,
				});
			}

			//initiate condition
			$( form_selector ).forminatorFrontCondition(this.settings.conditions, this.settings.calendar);

			//initiate forminator ui scripts
			this.init_fui( form_selector );

			//initiate datepicker
			$( form_selector ).find('.forminator-datepicker').forminatorFrontDatePicker(this.settings.calendar);

			// Handle responsive captcha
			this.responsive_captcha( form_selector );

			// Handle field counter
			this.field_counter( form_selector );

			// Handle number input
			this.field_number( form_selector );

			// Handle time fields
			this.field_time();

			// Handle upload field change
			$( form_selector ).find('.forminator-multi-upload').forminatorFrontMultiFile( this.$el );

			this.upload_field( form_selector );

			this.init_login_2FA();

			self.maybeRemoveDuplicateFields( form_selector );

			// Handle function on resize
			$(window).on('resize', function () {
				self.responsive_captcha( form_selector );
			});

			// Handle function on load
			$( window ).on( 'load', function () {
				// Repeat the function here, just in case our scripts gets loaded late
				self.maybeRemoveDuplicateFields( form_selector );
			});

			// We have to declate initialData here, after everything has been set initially, to prevent triggering change event.
			var initialData	= saveDraftExists ? this.$el.serializeArray() : '';
			this.$el.find( ".forminator-field input, .forminator-row input[type=hidden], .forminator-field select, .forminator-field textarea, .forminator-field-signature").on( 'change input', function (e) {
				if ( saveDraftExists && $saveDraft.hasClass( 'disabled' ) ) {
					clearTimeout( draftTimer );
					draftTimer = setTimeout( function() {
							self.maybe_enable_save_draft( $saveDraft, initialData );
						},
						500
					);
				}
			});

			if( 'undefined' !== typeof self.settings.hasLeads ) {
				if( 'beginning' === self.settings.form_placement ) {
					$('#forminator-module-' + this.settings.quiz_id ).css({
						'height': 0,
						'opacity': 0,
						'overflow': 'hidden',
						'visibility': 'hidden',
						'pointer-events': 'none',
						'margin': 0,
						'padding': 0,
						'border': 0
					});
				}
				if( 'end' === self.settings.form_placement ) {
					$( form_selector ).css({
						'height': 0,
						'opacity': 0,
						'overflow': 'hidden',
						'visibility': 'hidden',
						'pointer-events': 'none',
						'margin': 0,
						'padding': 0,
						'border': 0
					});
				}
			}

		},
		init_poll_form: function() {

			var self       = this,
				$fieldset  = this.$el.find( 'fieldset' ),
				$selection = this.$el.find( '.forminator-radio input' ),
				$input     = this.$el.find( '.forminator-input' ),
				$field     = $input.closest( '.forminator-field' )
				;

			// Load input states
			FUI.inputStates( $input );

			// Show input when option has been selected
			$selection.on( 'click', function() {

				// Reset
				$field.addClass( 'forminator-hidden' );
				$field.attr( 'aria-hidden', 'true' );
				$input.removeAttr( 'tabindex' );
				$input.attr( 'name', '' );

				var checked = this.checked,
					$id     = $( this ).attr( 'id' ),
					$name   = $( this ).attr( 'name' )
					;

				// Once an option has been chosen, remove error class.
				$fieldset.removeClass( 'forminator-has_error' );

				if ( self.$el.find( '.forminator-input#' + $id + '-extra' ).length ) {

					var $extra = self.$el.find( '.forminator-input#' + $id + '-extra' ),
						$extraField = $extra.closest( '.forminator-field' )
						;

					if ( checked ) {

						$extra.attr( 'name', $name + '-extra' );

						$extraField.removeClass( 'forminator-hidden' );
						$extraField.removeAttr( 'aria-hidden' );

						$extra.attr( 'tabindex', '-1' );
						$extra.focus();

					} else {

						$extraField.addClass( 'forminator-hidden' );
						$extraField.attr( 'aria-hidden', 'true' );

						$extra.removeAttr( 'tabindex' );

					}
				}

				return true;

			});

			// Disable options
			if ( this.$el.hasClass( 'forminator-poll-disabled' ) ) {

				this.$el.find( '.forminator-radio' ).each( function() {

					$( this ).addClass( 'forminator-disabled' );
					$( this ).find( 'input' ).attr( 'disabled', true );

				});
			}
		},

		init_quiz_form: function () {
			var self = this,
				lead_placement = 'undefined' !== typeof self.settings.form_placement ? self.settings.form_placement : '',
				quiz_id = 'undefined' !== typeof self.settings.quiz_id ? self.settings.quiz_id : 0;

			this.$el.find('.forminator-button:not(.forminator-quiz-start)').each(function () {
				$(this).prop("disabled", true);
			});

			this.$el.find('.forminator-answer input').each(function () {
				$(this).attr('checked', false);
			});

			this.$el.find('.forminator-result--info button').on('click', function () {
				location.reload();
			});

			$('#forminator-quiz-leads-' + quiz_id + ' .forminator-quiz-intro .forminator-quiz-start').on('click', function(e){
				e.preventDefault();
				$(this).closest( '.forminator-quiz-intro').hide();
				self.$el.prepend('<button class="forminator-button forminator-quiz-start forminator-hidden"></button>')
						.find('.forminator-quiz-start').trigger('click').remove();
			});

			this.$el.on('click', '.forminator-quiz-start', function (e) {
				e.preventDefault();
				self.$el.find('.forminator-quiz-intro').hide();
				self.$el.find('.forminator-pagination').removeClass('forminator-hidden');
				//initiate pagination
				var args = {
					totalSteps: self.$el.find('.forminator-pagination').length - 1, //subtract the last step with result
					step: 0,
					quiz: true
				};
				if ( self.settings.text_next ) {
					args.next_button = self.settings.text_next;
				}
				if ( self.settings.text_prev ) {
					args.prev_button = self.settings.text_prev;
				}
				$(self.element).forminatorFrontPagination(args);
			});

			if( 'end' !== lead_placement ) {
				this.$el.find('.forminator-submit-rightaway').on("click", function () {
					self.$el.submit();
					$(this).closest('.forminator-question').find('.forminator-submit-rightaway').addClass('forminator-has-been-disabled').attr('disabled', 'disabled');
				});
			}

			if( self.settings.hasLeads ) {
				if( 'beginning' === lead_placement ) {
					self.$el.css({
						'height': 0,
						'opacity': 0,
						'overflow': 'hidden',
						'visibility': 'hidden',
						'pointer-events': 'none',
						'margin': 0,
						'padding': 0,
						'border': 0
					});
				}
				if( 'end' === lead_placement ) {
					self.$el.closest('div').find('#forminator-module-' + self.settings.leads_id ).css({
						'height': 0,
						'opacity': 0,
						'overflow': 'hidden',
						'visibility': 'hidden',
						'pointer-events': 'none',
						'margin': 0,
						'padding': 0,
						'border': 0
					});
					$('#forminator-quiz-leads-' + quiz_id + ' .forminator-lead-form-skip' ).hide();
				}
			}

			this.$el.on('click', '.forminator-social--icon a', function (e) {
				e.preventDefault();
				var social        = $(this).data('social'),
				    url           = $(this).closest('.forminator-social--icons').data('url'),
				    message       = $(this).closest('.forminator-social--icons').data('message'),
				    message       = encodeURIComponent(message),
					 social_shares = {
						'facebook': 'https://www.facebook.com/sharer/sharer.php?u=' + url + '&quote=' + message,
						'twitter': 'https://twitter.com/intent/tweet?&url=' + url + '&text=' + message,
						'google': 'https://plus.google.com/share?url=' + url,
						'linkedin': 'https://www.linkedin.com/shareArticle?mini=true&url=' + url + '&title=' + message
					};

				if (social_shares[social] !== undefined) {
					var newwindow = window.open(social_shares[social], social, 'height=' + $(window).height() + ',width=' + $(window).width());
					if (window.focus) {
						newwindow.focus();
					}
					return false;
				}
			});

			this.$el.on('change', '.forminator-answer input', function (e) {
				var paginated      = !!$( this ).closest('.forminator-pagination').length,
					parent         = paginated ? $( this ).closest('.forminator-pagination') : self.$el,
					count          = parent.find('.forminator-answer input:checked').length,
				    amount_answers = parent.find('.forminator-question').length,
				    parentQuestion = $( this ).closest( '.forminator-question' ),
					isMultiChoice  = parentQuestion.data( 'multichoice' )
					;

				self.$el.find('.forminator-button:not(.forminator-button-back)').each(function () {
					var disabled = count < amount_answers;
					$( this ).prop('disabled', disabled);
					if ( paginated ) {
						if ( disabled ) {
							$( this ).addClass('forminator-disabled');
						} else {
							$( this ).removeClass('forminator-disabled');
						}
					}
				});

				// If multichoice is false, uncheck other options
				if( this.checked && false === isMultiChoice ) {
					parentQuestion
					.find( '.forminator-answer' )
					.not( $( this ).parent( '.forminator-answer' ) )
					.each( function( i, el ){
						$( el ).find( '> input' ).prop( 'checked', false );
					});
				}

			});
		},

		small_form: function () {

			var form      = $( this.element ),
				formWidth = form.width()
				;

			if ( 783 < Math.max( document.documentElement.clientWidth, window.innerWidth || 0 ) ) {

				if ( form.hasClass( 'forminator-size--small' ) ) {

					if ( 480 < formWidth ) {
						form.removeClass( 'forminator-size--small' );
					}
				} else {
					var hasHustle = form.closest('.hustle-content');

					if ( form.is(":visible") && 480 >= formWidth && ! hasHustle.length ) {
						form.addClass( 'forminator-size--small' );
					}
				}
			}
		},

		init_intlTelInput_validation: function ( form_selector ) {

			var form        = $( form_selector ),
				is_material = form.is('.forminator-design--material'),
				fields      = form.find('.forminator-field--phone');

			fields.each(function () {

				// Initialize intlTelInput plugin on each field with "format check" enabled and
				// set to check either "international" or "standard" phones.
				var is_national_phone = $(this).data('national_mode'),
					country           = $(this).data('country'),
					validation        = $(this).data('validation');

				if ('undefined' !== typeof (is_national_phone)) {

					if (is_material) {
						//$(this).unwrap('.forminator-input--wrap');
					}

					var args = {
						nationalMode: ('enabled' === is_national_phone) ? true : false,
						initialCountry: 'undefined' !== typeof ( country ) ? country : 'us',
						utilsScript: window.ForminatorFront.cform.intlTelInput_utils_script,
					};

					if ( 'undefined' !== typeof ( validation ) && 'standard' === validation ) {
						args.allowDropdown  = false;
					}

					$(this).intlTelInput(args);

					if ( ! is_material ) {
						$(this).closest( '.forminator-field' ).find( 'div.iti' ).addClass( 'forminator-phone' );
					} else {
						$(this).closest( '.forminator-field' ).find( 'div.iti' ).addClass( 'forminator-input-with-phone' );

						if ( $(this).closest( '.forminator-field' ).find( 'div.iti' ).hasClass( 'iti--allow-dropdown' ) ) {
							$(this).closest( '.forminator-field' ).find( '.forminator-label' ).addClass( 'iti--allow-dropdown' );
						}
					}

					// intlTelInput plugin adds a markup that's not compatible with 'material' theme when 'allowDropdown' is true (default).
					// If we're going to allow users to disable the dropdown, this should be adjusted accordingly.
					if (is_material) {
						//$(this).closest('.intl-tel-input.allow-dropdown').addClass('forminator-phone-intl').removeClass('intl-tel-input');
						//$(this).wrap('<div class="forminator-input--wrap"></div>');
					}
				}
			});

		},

		init_fui: function ( form_selector ) {

			var form        = $( form_selector ),
				input       = form.find( '.forminator-input' ),
				textarea    = form.find( '.forminator-textarea' ),
				select2     = form.find( '.forminator-select2' ),
				multiselect = form.find( '.forminator-multiselect' ),
				stripe		= form.find( '.forminator-stripe-element' )
				;

			var isDefault  = ( form.attr( 'data-design' ) === 'default' ),
				isBold     = ( form.attr( 'data-design' ) === 'bold' ),
				isFlat     = ( form.attr( 'data-design' ) === 'flat' ),
				isMaterial = ( form.attr( 'data-design' ) === 'material' )
				;

			if ( input.length ) {
				input.each( function() {
					FUI.inputStates( this );
				});
			}

			if ( textarea.length ) {
				textarea.each( function() {
					FUI.textareaStates( this );
				});
			}

			if ( 'function' === typeof FUI.select2 ) {
				FUI.select2( select2.length );
			}

			if ( multiselect.length ) {
				FUI.multiSelectStates( multiselect );
			}

			if ( form.hasClass( 'forminator-design--material' ) ) {
				if ( input.length ) {
					input.each( function() {
						FUI.inputMaterial( this );
					});
				}

				if ( textarea.length ) {
					textarea.each( function() {
						FUI.textareaMaterial( this );
					});
				}

				if ( stripe.length ) {
					stripe.each( function() {
						var field = $(this).closest('.forminator-field');
						var label = field.find('.forminator-label');

						if (label.length) {
							field.addClass('forminator-stripe-floating');
							// Add floating class
							label.addClass('forminator-floating--input');
						}
					});
				}
			}
		},

		responsive_captcha: function ( form_selector ) {
			$( form_selector ).find('.forminator-g-recaptcha').each(function () {
				var badge = $(this).data('badge'); // eslint-disable-line
				if ($(this).is(':visible') && 'inline' === badge ) {
					var width = $(this).parent().width(),
					    scale = 1;
					if (width < 302) {
						scale = width / 302;
					}
					$(this).css('transform', 'scale(' + scale + ')');
					$(this).css('-webkit-transform', 'scale(' + scale + ')');
					$(this).css('transform-origin', '0 0');
					$(this).css('-webkit-transform-origin', '0 0');
				}
			});
		},

		init_pagination: function ( form_selector ) {
			var self      = this,
			    num_pages = $( form_selector ).find(".forminator-pagination").length,
			    hash      = window.location.hash,
			    hashStep  = false,
			    step      = 0;

			if (num_pages > 0) {
				//find from hash
				if (typeof hash !== "undefined" && hash.indexOf('step-') >= 0) {
					hashStep = true;
					step     = hash.substr(6, 8);
				}

				$(this.element).forminatorFrontPagination({
					totalSteps: num_pages,
					hashStep: hashStep,
					step: step,
					inline_validation: self.settings.inline_validation,
					submitButtonClass: self.settings.submit_button_class
				});
			}
		},

		activate_field: function () {

			var form     = $( this.element );
			var input    = form.find( '.forminator-input' );
			var textarea = form.find( '.forminator-textarea' );

			function classFilled( el ) {

				var element       = $( el );
				var elementValue  = element.val().trim();
				var elementField  = element.closest( '.forminator-field' );
				var elementAnswer = element.closest( '.forminator-poll--answer' );

				var filledClass = 'forminator-is_filled';

				if ( '' !== elementValue ) {
					elementField.addClass( filledClass );
					elementAnswer.addClass( filledClass );
				} else {
					elementField.removeClass( filledClass );
					elementAnswer.removeClass( filledClass );
				}

				element.change( function( e ) {

					if ( '' !== elementValue ) {
						elementField.addClass( filledClass );
						elementAnswer.addClass( filledClass );
					} else {
						elementField.removeClass( filledClass );
						elementAnswer.removeClass( filledClass );
					}

					e.stopPropagation();

				});
			}

			function classHover( el ) {

				var element       = $( el );
				var elementField  = element.closest( '.forminator-field' );
				var elementAnswer = element.closest( '.forminator-poll--answer' );

				var hoverClass = 'forminator-is_hover';

				element.on( 'mouseover', function( e ) {
					elementField.addClass( hoverClass );
					elementAnswer.addClass( hoverClass );
					e.stopPropagation();
				}).on( 'mouseout', function( e ) {
					elementField.removeClass( hoverClass );
					elementAnswer.removeClass( hoverClass );
					e.stopPropagation();
				});
			}

			function classActive( el ) {

				var element       = $( el );
				var elementField  = element.closest( '.forminator-field' );
				var elementAnswer = element.closest( '.forminator-poll--answer' );

				var activeClass = 'forminator-is_active';

				element.focus( function( e ) {
					elementField.addClass( activeClass );
					elementAnswer.addClass( activeClass );
					e.stopPropagation();
				}).blur( function( e ) {
					elementField.removeClass( activeClass );
					elementAnswer.removeClass( activeClass );
					e.stopPropagation();
				});
			}

			function classError( el ) {

				var element       = $( el );
				var elementValue  = element.val().trim();
				var elementField  = element.closest( '.forminator-field' );
				var elementTime   = element.attr( 'data-field' );

				var timePicker = element.closest( '.forminator-timepicker' );
				var timeColumn = timePicker.parent();

				var errorField = elementField.find( '.forminator-error-message' );

				var errorClass = 'forminator-has_error';

				element.on( 'load change keyup keydown', function( e ) {

					if ( 'undefined' !== typeof elementTime && false !== elementTime ) {

						if ( 'hours' === element.data( 'field' ) ) {

							var hoursError = timeColumn.find( '.forminator-error-message[data-error-field="hours"]' );

							if ( '' !== elementValue && 0 !== hoursError.length ) {
								hoursError.remove();
							}
						}

						if ( 'minutes' === element.data( 'field' ) ) {

							var minutesError = timeColumn.find( '.forminator-error-message[data-error-field="minutes"]' );

							if ( '' !== elementValue && 0 !== minutesError.length ) {
								minutesError.remove();
							}
						}
					} else {

						if ( '' !== elementValue && errorField.text() ) {
							errorField.remove();
							elementField.removeClass( errorClass );
						}
					}

					e.stopPropagation();

				});
			}

			if ( input.length ) {

				input.each( function() {
					//classFilled( this );
					//classHover( this );
					//classActive( this );
					classError( this );
				});
			}

			if ( textarea.length ) {

				textarea.each( function() {
					//classFilled( this );
					//classHover( this );
					//classActive( this );
					classError( this );
				});
			}

			form.find('select.forminator-select2 + .forminator-select').each(function () {

				var $select = $(this);

				// Set field active class on hover
				$select.on('mouseover', function (e) {
					e.stopPropagation();
					$(this).closest('.forminator-field').addClass('forminator-is_hover');

				}).on('mouseout', function (e) {
					e.stopPropagation();
					$(this).closest('.forminator-field').removeClass('forminator-is_hover');

				});

				// Set field active class on focus
				$select.on('click', function (e) {
					e.stopPropagation();
					checkSelectActive();
					if ($select.hasClass('select2-container--open')) {
						$(this).closest('.forminator-field').addClass('forminator-is_active');
					} else {
						$(this).closest('.forminator-field').removeClass('forminator-is_active');
					}

				});


			});

			function checkSelectActive() {
				if (form.find('.select2-container').hasClass('select2-container--open')) {
					setTimeout(checkSelectActive, 300);
				} else {
					form.find('.select2-container').closest('.forminator-field').removeClass('forminator-is_active');
				}
			}
		},

		field_counter: function ( form_selector ) {
			var form = $( form_selector ),
				submit_button = form.find('.forminator-button-submit');

			form.find('.forminator-input, .forminator-textarea').each(function () {
				var $input   = $(this),
				    numwords = 0,
				    count    = 0;

				$input.on('keydown', function (e) {
					if ( ! $(this).hasClass('forminator-textarea') && e.keyCode === 13 ) {
						e.preventDefault();
						if ( submit_button.is(":visible") ) {
							submit_button.trigger('click');
						}
						return false;
					}
				});

				$input.on('change keyup keydown', function (e) {
					e.stopPropagation();
					var $field = $(this).closest('.forminator-col'),
					    $limit = $field.find('.forminator-description span')
					;

					if ($limit.length) {
						if ($limit.data('limit')) {
							if ($limit.data('type') !== "words") {
								count = $( '<div>' + $(this).val() + '</div>' ).text().length;
							} else {
								count = $(this).val().trim().split(/\s+/).length;

                                // Prevent additional words from being added when limit is reached.
                                numwords = $(this).val().trim().split(/\s+/).length;
                                if ( numwords >= $limit.data( 'limit' ) ) {
                                    // Allow delete and backspace when limit is reached.
									if( e.which === 32 ) {
										e.preventDefault();
									}
                                }
							}
							$limit.html(count + ' / ' + $limit.data('limit'));
						}
					}
				});

			});
		},

		field_number: function ( form_selector ) {
			// var form = $(this.element);
			// form.find('input[type=number]').on('change keyup', function () {
			// 	if( ! $(this).val().match(/^\d+$/) ){
			// 		var sanitized = $(this).val().replace(/[^0-9]/g, '');
			// 		$(this).val(sanitized);
			// 	}
			// });
			var form = $( form_selector );
			form.find('input[type=number]').each(function () {
				$(this).keypress(function (e) {
					var i;
					var allowed = [44, 45, 46];
					var key     = e.which;

					for (i = 48; i < 58; i++) {
						allowed.push(i);
					}

					if (!(allowed.indexOf(key) >= 0)) {
						e.preventDefault();
					}
				});
			});

			form.find('.forminator-number--field, .forminator-currency, .forminator-calculation').each(function () {
				var inputType = $( this ).attr( 'type' );
				if ( 'number' === inputType ) {
					var decimals = $( this ).data( 'decimals' );
					$( this ).change( function ( e ) {
						this.value = parseFloat( this.value ).toFixed( decimals );
					});
				}
				/*
				* If you need to retrieve the formatted (masked) value, you can use something like this:
				* $element.inputmask({'autoUnmask' : false});
				* var value = $element.val();
				* $element.inputmask({'autoUnmask' : true});
				*/
				$( this ).inputmask({
					'alias': 'decimal',
					'rightAlign': false,
					'digitsOptional': false,
					'showMaskOnHover': false,
					'autoUnmask' : true, // Automatically unmask the value when retrieved - this prevents the "Maximum call stack size exceeded" console error that happens in some forms that contain number/calculation fields with localized masks.
					'removeMaskOnSubmit': true,
				});
			});
		},

		field_time: function () {
			$('.forminator-input-time').on('input', function (e) {
				var $this = $(this),
				    value = $this.val()
				;

				// Allow only 2 digits for time fields
				if (value && value.length >= 2) {
					$this.val(value.substr(0, 2));
				}
			});
		},

		init_login_2FA: function () {
			var self = this;
			this.two_factor_providers( 'totp' );
			$('body').on('click', '.forminator-2fa-link', function () {
				self.$el.find('#login_error').remove();
				self.$el.find('.notification').empty();
				var slug = $(this).data('slug');
				self.two_factor_providers( slug );
				if ('fallback-email' === slug) {
					self.resend_code();
				}
			});
			this.$el.find('.wpdef-2fa-email-resend input').on('click', function () {
				self.resend_code();
			});
		},
		two_factor_providers: function ( slug ) {
			var self = this;
			self.$el.find('.forminator-authentication-box').hide();
			self.$el.find('.forminator-authentication-box input').attr( 'disabled', true );
			self.$el.find( '#forminator-2fa-' + slug ).show();
			self.$el.find( '#forminator-2fa-' + slug + ' input' ).attr( 'disabled', false );
			if ( self.$el.find('.forminator-2fa-link').length > 0 ) {
				self.$el.find('.forminator-2fa-link').hide();
				self.$el.find('.forminator-2fa-link:not(#forminator-2fa-link-'+ slug +')').each(function() {
					self.$el.find('.forminator-auth-method').val( slug );
					$( this ).find('input').attr( 'disabled', false );
					$( this ).show();
				});
			}
		},

		// Logic for FallbackEmail method.
		resend_code: function () {
			// Work with the button 'Resen Code'.
			var self  = this;
			var that  = $('input[name="button_resend_code"]');
			var token = $('.forminator-auth-token');
			let data = {
				action: 'forminator_2fa_fallback_email',
				data: JSON.stringify({
					'token': token
				})
			};
			$.ajax({
				type: 'POST',
				url: window.ForminatorFront.ajaxUrl,
				data: data,
				beforeSend: function () {
					that.attr('disabled', 'disabled');
					$('.def-ajaxloader').show();
				},
				success: function (data) {
					that.removeAttr('disabled');
					$('.def-ajaxloader').hide();
					$('.notification').text(data.data.message);
				}
			})
		},

		material_field: function () {
			/*
			var form = $(this.element);
			if (form.is('.forminator-design--material')) {
				var $input    = form.find('.forminator-input--wrap'),
				    $textarea = form.find('.forminator-textarea--wrap'),
				    $date     = form.find('.forminator-date'),
				    $product  = form.find('.forminator-product');

				var $navigation = form.find('.forminator-pagination--nav'),
				    $navitem    = $navigation.find('li');

				$('<span class="forminator-nav-border"></span>').insertAfter($navitem);

				$input.prev('.forminator-field--label').addClass('forminator-floating--input');
				$input.closest('.forminator-phone-intl').prev('.forminator-field--label').addClass('forminator-floating--input');
				$textarea.prev('.forminator-field--label').addClass('forminator-floating--textarea');

				if ($date.hasClass('forminator-has_icon')) {
					$date.prev('.forminator-field--label').addClass('forminator-floating--date');
				} else {
					$date.prev('.forminator-field--label').addClass('forminator-floating--input');
				}
			}
			*/
		},

		toggle_file_input: function() {

			var $form = $( this.element );

			$form.find( '.forminator-file-upload' ).each( function() {

				var $field = $( this );
				var $input = $field.find( 'input' );
				var $remove = $field.find( '.forminator-button-delete' );

				// Toggle remove button depend on input value
				if ( '' !== $input.val() ) {
					$remove.show(); // Show remove button
				} else {
					$remove.hide(); // Hide remove button
				}
			});
		},

		upload_field: function ( form_selector ) {

			var self = this,
			    form = $( form_selector )
			;
			// Toggle file remove button
			this.toggle_file_input();

			// Handle remove file button click
			form.find( '.forminator-button-delete' ).on('click', function (e) {

				e.preventDefault();

				var $self  = $( this ),
				    $input = $self.siblings('input'),
				    $label = $self.closest( '.forminator-file-upload' ).find('> span')
					;

				// Cleanup
				$input.val('');
				$label.html( $label.data( 'empty-text' ) );
				$self.hide();

			});

			form.find( '.forminator-input-file, .forminator-input-file-required' ).on('change', function () {
				var $nameLabel = $(this).closest( '.forminator-file-upload' ).find( '> span' ),
					vals = $(this).val(),
					val  = vals.length ? vals.split('\\').pop() : ''
				;

				$nameLabel.text(val);

				self.toggle_file_input();
			});

			form.find( '.forminator-button-upload' ).on( 'click', function (e) {
				e.preventDefault();

				var $id        = $(this).attr('data-id'),
				    $target    = form.find('input#' + $id)
					;

				$target.trigger('click');
			});

			form.find( '.forminator-input-file, .forminator-input-file-required' ).on('change', function (e) {

				e.preventDefault();

				var $file   = $(this)[0].files.length,
				    $remove = $(this).find('.forminator-button-delete');

				if ($file === 0) {
					$remove.hide();
				} else {
					$remove.show();
				}

			});
		},

        // Remove duplicate fields created by other plugins/themes
		maybeRemoveDuplicateFields: function ( form_selector ) {
            var form = $( form_selector );

            // Check for Neira Lite theme
            if ( $( document ).find( "link[id='neira-lite-style-css']" ).length ) {
                var duplicateSelect  = form.find( '.forminator-select-container' ).next( '.chosen-container' ),
                    duplicateSelect2 = form.find( 'select.forminator-select2 + .forminator-select' ).next( '.chosen-container' ),
                    duplicateAddress = form.find( '.forminator-select' ).next( '.chosen-container' )
                ;

                if ( 0 !== duplicateSelect.length ) {
                    duplicateSelect.remove();
                }
                if ( 0 !== duplicateSelect2.length ) {
                    duplicateSelect2.remove();
                }
                if ( 0 !== duplicateAddress.length ) {
                    duplicateAddress.remove();
                }
            }
		},

		renderCaptcha: function (captcha_field) {
			var self = this;
			//render captcha only if not rendered
			if (typeof $(captcha_field).data('forminator-recapchta-widget') === 'undefined') {
				var size = $(captcha_field).data('size'),
				    data = {
					    sitekey: $(captcha_field).data('sitekey'),
					    theme: $(captcha_field).data('theme'),
					    size: size
				    };

				if (size === 'invisible') {
					data.badge    = $(captcha_field).data('badge');
					data.callback = function (token) {
						$(self.element).trigger('submit.frontSubmit');
					};
				} else {
					data.callback = function () {
						$(captcha_field).parent( '.forminator-col' )
							.removeClass( 'forminator-has_error' )
							.remove( '.forminator-error-message' );
					};
				}

				if (data.sitekey !== "") {
					// noinspection Annotator
					var widget = window.grecaptcha.render(captcha_field, data);
					// mark as rendered
					$(captcha_field).data('forminator-recapchta-widget', widget);
					this.addCaptchaAria( captcha_field );
					this.responsive_captcha();
				}
			}
		},

		renderHcaptcha: function ( captcha_field ) {
			var self = this;
			//render hcaptcha only if not rendered
			if (typeof $( captcha_field ).data( 'forminator-hcaptcha-widget' ) === 'undefined') {
				var size = $( captcha_field ).data( 'size' ),
				    data = {
					    sitekey: $( captcha_field ).data( 'sitekey' ),
					    theme: $( captcha_field ).data( 'theme' ),
					    size: size
				    };

				if ( size === 'invisible' ) {
					data.callback = function ( token ) {
						$( self.element ).trigger( 'submit.frontSubmit' );
					};
				} else {
					data.callback = function () {
						$( captcha_field ).parent( '.forminator-col' )
							.removeClass( 'forminator-has_error' )
							.remove( '.forminator-error-message' );
					};
				}

				if ( data.sitekey !== "" ) {
					// noinspection Annotator
					var widgetId = hcaptcha.render( captcha_field, data );
					// mark as rendered
					$( captcha_field ).data( 'forminator-hcaptcha-widget', widgetId );
					// this.addCaptchaAria( captcha_field );
					// this.responsive_captcha();
				}
			}
		},

		addCaptchaAria: function ( captcha_field ) {
			var gRecaptchaResponse = $( captcha_field ).find( '.g-recaptcha-response' ),
				gRecaptcha = $( captcha_field ).find( '>div' );

			if ( 0 !== gRecaptchaResponse.length ) {
				gRecaptchaResponse.attr( "aria-hidden", "true" );
				gRecaptchaResponse.attr( "aria-label", "do not use" );
				gRecaptchaResponse.attr( "aria-readonly", "true" );
			}
			if ( 0 !== gRecaptcha.length ) {
				gRecaptcha.css( 'z-index', 99 );
			}
		},

		hide: function () {
			this.$el.hide();
		},
		/**
		 * Return JSON object if possible
		 *
		 * We tried our best here
		 * if there is an error/exception, it will return empty object/array
		 *
		 * @param string
		 * @param type ('array'/'object')
		 */
		maybeParseStringToJson: function (string, type) {
			var object = {};
			// already object
			if (typeof string === 'object') {
				return string;
			}

			if (type === 'object') {
				string = '{' + string.trim() + '}';
			} else if (type === 'array') {
				string = '[' + string.trim() + ']';
			} else {
				return {};
			}

			try {
				// remove trailing comma, duh
				/**
				 * find `,`, after which there is no any new attribute, object or array.
				 * New attribute could start either with quotes (" or ') or with any word-character (\w).
				 * New object could start only with character {.
				 * New array could start only with character [.
				 * New attribute, object or array could be placed after a bunch of space-like symbols (\s).
				 *
				 * Feel free to hack this regex if you got better idea
				 * @type {RegExp}
				 */
				var trailingCommaRegex = /\,(?!\s*?[\{\[\"\'\w])/g;
				string                 = string.replace(trailingCommaRegex, '');

				object = JSON.parse(string);
			} catch (e) {
				console.error(e.message);
				if (type === 'object') {
					object = {};
				} else if (type === 'array') {
					object = [];
				}
			}

			return object;

		},

		/**
		 * Render Stripe once it's available
		 *
		 * @param string
		 * @param type ('array'/'object')
		 */
		renderStripe: function( form, stripe_payment, stripeLoadCounter = 0 ) {
			var self = this;

			setTimeout( function() {
				stripeLoadCounter++;

				if ( 'undefined' !== typeof Stripe ) {

					$( form.element ).forminatorFrontPayment({
						type: 'stripe',
						paymentEl: stripe_payment,
						paymentRequireSsl: form.settings.payment_require_ssl,
						generalMessages: form.settings.general_messages,
						has_loader: form.settings.has_loader,
						loader_label: form.settings.loader_label,
					});

				// Retry checking for 30 seconds
				} else if ( stripeLoadCounter < 300 ) {
					self.renderStripe( form, stripe_payment, stripeLoadCounter );
				} else {
					console.error( 'Failed to load Stripe.' );
				}
			}, 100 );
		},

        // Enable save draft button once a change is made
		maybe_enable_save_draft: function ( $saveDraft, initialData ) {
			var changedData = this.$el.serializeArray(),
				hasChanged	= false,
				hasSig		= this.$el.find( '.forminator-field-signature' ).length ? true : false
				;

			// Remove signature field from changedData, will process later
			changedData = changedData.filter( function( val ) {
				return val.name.indexOf( 'ctlSignature' ) === -1 ;
			});

			initialData = JSON.stringify( initialData );
			changedData = JSON.stringify( changedData );

			// Check for field changes
			if ( initialData !== changedData ) {
				hasChanged = true;
			}

			// Check for signature change
			if ( hasSig && false === hasChanged ) {
				this.$el.find( '.forminator-field-signature' ).each( function(e) {
					var sigPrefix = $( this ).find( '.signature-prefix' ).val();

					if (
						0 !== $( this ).find( '#ctlSignature' + sigPrefix + '_data' ).length &&
						'' !== $( this ).find( '#ctlSignature' + sigPrefix + '_data' ).val()
					) {
						hasChanged = true;
						return false;
					}
				});
			}

			if ( hasChanged ) {
				$saveDraft.removeClass( 'disabled' );
			} else {
				$saveDraft.addClass( 'disabled' );
			}
		},

	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFront(this, options));
			}
		});
	};

	// hook from wp_editor tinymce
	$(document).on('tinymce-editor-init', function (event, editor) {
		// trigger editor change to save value to textarea,
		// default wp tinymce textarea update only triggered when submit
		var count  = 0;
		editor.on('change', function () {
			var editor_id = editor.id,
				$field = $('#' + editor_id ).closest('.forminator-col'),
				$limit = $field.find('.forminator-description span')
			;

			// only forminator
			if ( -1 !== editor_id.indexOf( 'forminator-field-textarea-' ) ) {
				editor.save();
				$field.find( '#' + editor_id ).trigger( 'change' );
			}

			if ($limit.length) {
				if ($limit.data('limit')) {
					if ($limit.data('type') !== "words") {
						count = editor.getContent({ format: 'text' }).length;
					} else {
						count = editor.getContent({ format: 'text' }).split(/\s+/).length;
					}
					$limit.html(count + ' / ' + $limit.data('limit'));
				}
			}
		});

		// Make the visual editor and html editor the same height
		if ( $( '#' + editor.id + '_ifr' ).is( ':visible' ) ) {
			$( '#' + editor.id + '_ifr' ).height( $( '#' + editor.id ).height() );
		}
	});

	$( document ).on( 'click', '.forminator-copy-btn', function( e ) {
		forminatorCopyTextToClipboard( $( this ).prev( '.forminator-draft-link' ).val() );
		if ( ! $( this ).hasClass( 'copied' ) ) {
			$( this ).addClass( 'copied' )
			$( this ).prepend( '&check;  ' );
		}
	} );

	// Copy: Async + Fallback
	// https://stackoverflow.com/a/30810322
	function forminatorFallbackCopyTextToClipboard( text ) {
		var textArea = document.createElement("textarea");
		textArea.value = text;

		// Avoid scrolling to bottom
		textArea.style.top = "0";
		textArea.style.left = "0";
		textArea.style.position = "fixed";

		document.body.appendChild(textArea);
		textArea.focus();
		textArea.select();

		try {
			var successful = document.execCommand('copy');
			var msg = successful ? 'successful' : 'unsuccessful';
			// console.log('Fallback: Copying text command was ' + msg);
		} catch (err) {
			// console.error('Fallback: Oops, unable to copy', err);
		}

		document.body.removeChild(textArea);
	}

	function forminatorCopyTextToClipboard (text ) {
		if (!navigator.clipboard) {
			forminatorFallbackCopyTextToClipboard(text);
			return;
		}
		navigator.clipboard.writeText(text).then(function() {
			// console.log('Async: Copying to clipboard was successful!');
		}, function(err) {
			// console.error('Async: Could not copy text: ', err);
		});
	}

	// Focus to nearest input when label is clicked
	function focus_to_nearest_input() {
		$( '.forminator-custom-form' ).find( '.forminator-label' ).on( 'click', function ( e ) {
			e.preventDefault();
			var fieldLabel = $( this );

			fieldLabel.next( '#' + fieldLabel.attr( 'for' ) ).focus();
		});
	}

	focus_to_nearest_input();
	$( document ).on( 'after.load.forminator', focus_to_nearest_input );

	// Elementor Popup show event
	jQuery( document ).on( 'elementor/popup/show', () => {
		forminator_render_captcha();
		forminator_render_hcaptcha();
	} );

})(jQuery, window, document);

// noinspection JSUnusedGlobalSymbols
var forminator_render_captcha = function () {
	// TODO: avoid conflict with another plugins that provide recaptcha
	//  notify forminator front that grecaptcha has loaded and can be used
	jQuery('.forminator-g-recaptcha').each(function () {
		// find closest form
		var thisCaptcha = jQuery(this),
			form 		= thisCaptcha.closest('form');

		if (form.length > 0) {
			window.setTimeout( function() {
				var forminatorFront = form.data( 'forminatorFront' );
				if (typeof forminatorFront !== 'undefined') {
					forminatorFront.renderCaptcha( thisCaptcha[0] );
				}
			}, 100 );
		}
	});
};

// noinspection JSUnusedGlobalSymbols
var forminator_render_hcaptcha = function () {
	// TODO: avoid conflict with another plugins that provide hcaptcha
	//  notify forminator front that hcaptcha has loaded and can be used
	jQuery('.forminator-hcaptcha').each(function () {
		// find closest form
		var thisCaptcha = jQuery(this),
			form 		= thisCaptcha.closest('form');

		if (form.length > 0) {
			window.setTimeout( function() {
				var forminatorFront = form.data( 'forminatorFront' );
				if (typeof forminatorFront !== 'undefined') {
					forminatorFront.renderHcaptcha( thisCaptcha[0] );
				}
			}, 100 );
		}
	});
};

// Source: http://stackoverflow.com/questions/497790
var forminatorDateUtil = {
	month_number: function( v ) {
		var months_short = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
		var months_full = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
		if( v.constructor === Number ) {
			return v;
		}
		var n = NaN;
		if( v.constructor === String ) {
			v = v.toLowerCase();
			var index = months_short.indexOf( v );
			if( index === -1 ) {
				index = months_full.indexOf( v );
			}
			n = ( index === -1 ) ? NaN : index;
		}

		return n;
	},
    convert: function( d ) {
        // Converts the date in d to a date-object. The input can be:
        //   a date object: returned without modification
        //  an array      : Interpreted as [year,month,day]. NOTE: month is 0-11.
        //   a number     : Interpreted as number of milliseconds
        //                  since 1 Jan 1970 (a timestamp)
        //   a string     : Any format supported by the javascript engine, like
        //                  "YYYY/MM/DD", "MM/DD/YYYY", "Jan 31 2009" etc.
        //  an object     : Interpreted as an object with year, month and date
        //                  attributes.  **NOTE** month is 0-11.
        return (
            d.constructor === Date   ? d :
            d.constructor === Array  ? new Date( d[0], this.month_number( d[1] ), d[2] ) :
            d.constructor === Number ? new Date( d ) :
            d.constructor === String ? new Date( d ) :
            typeof d === "object"    ? new Date( d.year, this.month_number( d.month ), d.date ) :
            NaN
        );
    },
    compare: function( a, b ) {
        // Compare two dates (could be of any type supported by the convert
        // function above) and returns:
        //  -1 : if a < b
        //   0 : if a = b
        //   1 : if a > b
        // NaN : if a or b is an illegal date
        // NOTE: The code inside isFinite does an assignment (=).
        return (
            isFinite( a = this.convert( a ).valueOf() ) &&
            isFinite( b = this.convert( b ).valueOf() ) ?
            ( a > b ) - ( a < b ) :
            NaN
        );
    },
    inRange: function( d, start, end ) {
        // Checks if date in d is between dates in start and end.
        // Returns a boolean or NaN:
        //    true  : if d is between start and end (inclusive)
        //    false : if d is before start or after end
        //    NaN   : if one or more of the dates is illegal.
        // NOTE: The code inside isFinite does an assignment (=).
       return (
            isFinite( d = this.convert( d ).valueOf() ) &&
            isFinite( start = this.convert( start ).valueOf() ) &&
            isFinite( end = this.convert( end ).valueOf() ) ?
            start <= d && d <= end :
            NaN
        );
    },

    diffInDays: function( d1, d2 ) {
		d1 = this.convert( d1 );
		d2 = this.convert( d2 );
		if( typeof d1.getMonth !== 'function' || typeof d2.getMonth !== 'function' ) {
			return NaN;
		}

	    var t2 = d2.getTime();
	    var t1 = d1.getTime();

	    return parseFloat((t2-t1)/(24*3600*1000));
	},

	diffInWeeks: function( d1, d2 ) {
		d1 = this.convert( d1 );
		d2 = this.convert( d2 );
		if( typeof d1.getMonth !== 'function' || typeof d2.getMonth !== 'function' ) {
			return NaN;
		}

	    var t2 = d2.getTime();
	    var t1 = d1.getTime();

	    return parseInt((t2-t1)/(24*3600*1000*7));
	},

	diffInMonths: function( d1, d2 ) {
		d1 = this.convert( d1 );
		d2 = this.convert( d2 );
		if( typeof d1.getMonth !== 'function' || typeof d2.getMonth !== 'function' ) {
			return NaN;
		}

	    var d1Y = d1.getFullYear();
	    var d2Y = d2.getFullYear();
	    var d1M = d1.getMonth();
	    var d2M = d2.getMonth();

	    return (d2M+12*d2Y)-(d1M+12*d1Y);
	},

	diffInYears: function( d1, d2 ) {
		d1 = this.convert( d1 );
		d2 = this.convert( d2 );
		if( typeof d1.getMonth !== 'function' || typeof d2.getMonth !== 'function' ) {
			return NaN;
		}

	    return d2.getFullYear()-d1.getFullYear();
	}
};

// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "forminatorFrontCalculate",
	    defaults   = {
		    forminatorFields: [],
		    generalMessages: {},
	    };

	// The actual plugin constructor
	function ForminatorFrontCalculate(element, options) {
		this.element = element;
		this.$el     = $(this.element);

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings          = $.extend({}, defaults, options);
		this._defaults         = defaults;
		this._name             = pluginName;
		this.calculationFields = [];
		this.triggerInputs     = [];
		this.isError           = false;
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFrontCalculate.prototype, {
		init: function () {
			var self              = this;

			// find calculation fields
			var calculationInputs = this.$el.find('input.forminator-calculation');

			if (calculationInputs.length > 0) {

				calculationInputs.each(function () {
					self.calculationFields.push({
						$input: $(this),
						formula: $(this).data('formula'),
						name: $(this).attr('name'),
						isHidden: $(this).data('isHidden'),
						precision: $(this).data('precision'),
						//separators: $(this).data('separators'),
					});

					// isHidden
					if ($(this).data('isHidden')) {
						$(this).closest('.forminator-col').addClass('forminator-hidden forminator-hidden-option');
						var rowField = $(this).closest('.forminator-row');
						rowField.addClass('forminator-hidden-option');

						if (rowField.find('> .forminator-col:not(.forminator-hidden)').length === 0) {
							rowField.addClass('forminator-hidden');
						}
					}
				});

				var memoizeTime = this.settings.memoizeTime || 300;

				this.debouncedReCalculateAll = this.debounce(this.recalculateAll, 1000);
				this.memoizeDebounceRender = this.memoize(this.recalculate, memoizeTime);

				this.$el.on('forminator:field:condition:toggled', function (e) {
					self.debouncedReCalculateAll();
				});

				this.parseCalcFieldsFormula();
				this.attachEventToTriggeringFields();
				this.debouncedReCalculateAll();
			}
		},

		// Memoize an expensive function by storing its results.
		memoize: function(func, wait) {
			var memo = {};
			var timeout;
			var slice = Array.prototype.slice;

			return function() {
				var args = slice.call(arguments);

				var later = function() {
					timeout = null;
					memo    = {};
				};

				clearTimeout(timeout);
				timeout = setTimeout(later, wait);

				if (args[0].name in memo) {
					return memo[args[0].name];
				} else {
					return (memo[args[0].name] = func.apply(this, args));
				}
			}
		},

		debounce: function (func, wait, immediate) {
			var timeout;
			return function() {
				var context = this, args = arguments;
				var later = function() {
					timeout = null;
					if (!immediate) func.apply(context, args);
				};
				var callNow = immediate && !timeout;
				clearTimeout(timeout);
				timeout = setTimeout(later, wait);
				if (callNow) func.apply(context, args);
			};
		},

		parseCalcFieldsFormula: function () {
			for (var i = 0; i < this.calculationFields.length; i++) {
				var calcField = this.calculationFields[i];
				var formula   = calcField.formula;

				// Disable formula expand to allow formula calculation based on conditions
				//formula          = this.maybeExpandCalculationFieldOnFormula(formula);

				calcField.formula = formula;

				this.calculationFields[i] = calcField;
			}
		},

		maybeExpandCalculationFieldOnFormula: function (formula) {

			var joinedFieldTypes      = this.settings.forminatorFields.join('|');
			var incrementFieldPattern = "(" + joinedFieldTypes + ")-\\d+";
			var pattern               = new RegExp('\\{(' + incrementFieldPattern + ')(\\-[A-Za-z-_]+)?\\}', 'g');
			var parsedFormula         = formula;

			var matches;
			var needExpand = false;
			while (matches = pattern.exec(formula)) {
				var fullMatch = matches[0];
				var inputName = matches[1];
				var fieldType = matches[2];

				var replace = fullMatch;

				if (fullMatch === undefined || inputName === undefined || fieldType === undefined) {
					continue;
				}

				if (fieldType === 'calculation') {
					needExpand = true;

					// find input with name, and get formula
					// bracketify
					replace = '(' + this.$el.find('input[name="' + inputName + '"]').data('formula') + ')';
				}

				parsedFormula = parsedFormula.replace(fullMatch, replace);
			}

			if (needExpand) {
				parsedFormula = this.maybeExpandCalculationFieldOnFormula(parsedFormula);
			}

			return parsedFormula;
		},

		findTriggerInputs: function (calcField) {
			var formula               = calcField.formula;
			var joinedFieldTypes      = this.settings.forminatorFields.join('|');
			var incrementFieldPattern = "(" + joinedFieldTypes + ")-\\d+";
			var pattern               = new RegExp('\\{(' + incrementFieldPattern + ')(\\-[A-Za-z-_]+)?\\}', 'g');

			var matches;
			while (matches = pattern.exec(formula)) {
				var fullMatch = matches[0];
				var inputName = matches[1];
				var fieldType = matches[2];

				if (fullMatch === undefined || inputName === undefined || fieldType === undefined) {
					continue;
				}

				var formField = this.get_form_field(inputName);

				if (!formField.length) {
					continue;
				}

				var calcFields = formField.data('calcFields');
				if (calcFields === undefined) {
					calcFields = [];
				}

				var calcFieldAlreadyExist = false;

				for (var j = 0; j < calcFields.length; j++) {
					var currentCalcField = calcFields[j];
					if (currentCalcField.name === calcField.name) {
						calcFieldAlreadyExist = true;
						break;
					}
				}

				if (!calcFieldAlreadyExist) {
					calcFields.push(calcField);
				}

				formField.data('calcFields', calcFields);
				this.triggerInputs.push(formField);
			}
		},

		// taken from forminatorFrontCondition
		get_form_field: function (element_id) {
			//find element by suffix -field on id input (default behavior)
			var $form_id = this.$el.data( 'form-id' ),
				$uid 	 = this.$el.data( 'uid' ),
				$element = this.$el.find('#forminator-form-' + $form_id + '__field--' + element_id + '_' + $uid );
			if ( $element.length === 0 ) {
				var $element = this.$el.find('#' + element_id + '-field' );
				if ($element.length === 0) {
					//find element by its on name (for radio on singlevalue)
					$element = this.$el.find('input[name=' + element_id + ']');
					if ($element.length === 0) {
						// for text area that have uniqid, so we check its name instead
						$element = this.$el.find('textarea[name=' + element_id + ']');
						if ($element.length === 0) {
							//find element by its on name[] (for checkbox on multivalue)
							$element = this.$el.find('input[name="' + element_id + '[]"]');
							if ($element.length === 0) {
								//find element by direct id (for name field mostly)
								//will work for all field with element_id-[somestring]
								$element = this.$el.find('#' + element_id);
							}
						}
					}
				}
			}

			return $element;
		},

		attachEventToTriggeringFields: function () {
			var self = this;
			for (var i = 0; i < this.calculationFields.length; i++) {
				var calcField = this.calculationFields[i];
				this.findTriggerInputs(calcField);
			}

			if (this.triggerInputs.length > 0) {
				var cFields = [];
				for (var j = 0; j < this.triggerInputs.length; j++) {
					var $input = this.triggerInputs[j];
					var inputId = $input.attr('id');

					if (cFields.indexOf(inputId) < 0) {
						$input.on('change.forminatorFrontCalculate, blur', function () {
							var calcFields = $(this).data('calcFields');

							if (calcFields !== undefined && calcFields.length > 0) {
								for (var k = 0; k < calcFields.length; k++) {
									var calcField = calcFields[k];

									if(self.field_is_checkbox($(this)) || self.field_is_radio($(this))) {
										self.recalculate(calcField);
									} else {
										self.memoizeDebounceRender(calcField);
									}
								}
							}
						});

						cFields.push(inputId);
					}
				}
			}
		},

		recalculateAll: function () {
			for (var i = 0; i < this.calculationFields.length; i++) {
				this.recalculate(this.calculationFields[i]);
			}
		},

		recalculate: function (calcField) {
			var $input = calcField.$input;

			this.hideErrorMessage($input);

			var formula = this.maybeReplaceFieldOnFormula(calcField.formula);

			var res     = 0;
			var calc    = new window.forminatorCalculator(formula);

			try {
				res = calc.calculate();
				if (!isFinite(res)) {
					throw ('Infinity calculation result.');
				}
				// Support cases like 1.005. Correct result is 1.01.
				res = ( +( Math.round( res + `e+${calcField.precision}` )  + `e-${calcField.precision}` ) ).toFixed(calcField.precision);
			} catch (e) {
				this.isError = true;
				console.log(e);
				// override error message
				this.displayErrorMessage( $input, this.settings.generalMessages.calculation_error );
				res = '0';
			}

			if ($input.val() !== String(res)) {
				var decimal_point = $input.data('decimal-point');
				res = String(res).replace(".", decimal_point );
				$input.val(res).trigger("change");
			}
		},

		maybeReplaceFieldOnFormula: function (formula) {
			var joinedFieldTypes      = this.settings.forminatorFields.join('|');
			var incrementFieldPattern = "(" + joinedFieldTypes + ")-\\d+";
			var pattern               = new RegExp('\\{(' + incrementFieldPattern + ')(\\-[A-Za-z-_]+)?\\}', 'g');
			var parsedFormula         = formula;

			var matches;
			while (matches = pattern.exec(formula)) {
				var fullMatch = matches[0];
				var inputName = matches[1];
				var fieldType = matches[2];

				var replace = fullMatch;

				if (fullMatch === undefined || inputName === undefined || fieldType === undefined) {
					continue;
				}

				if(this.is_hidden(inputName)) {
					replace = 0;
					var quotedOperand = fullMatch.replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:])/g, "\\$1");
					var regexp = new RegExp('([\\+\\-\\*\\/]?)[^\\+\\-\\*\\/\\(]*' + quotedOperand + '[^\\)\\+\\-\\*\\/]*([\\+\\-\\*\\/]?)');
					var mt = regexp.exec(formula);
					if (mt) {
						// if operand in multiplication or division set value = 1
						if (mt[1] === '*' || mt[1] === '/' || mt[2] === '*' || mt[2] === '/') {
							replace = 1;
						}
					}
				} else {
					if (fieldType === 'calculation') {
						var calcField = this.get_calculation_field(inputName);

						if (calcField) {
							this.memoizeDebounceRender( calcField );
						}
					}

					replace = this.get_field_value(inputName);
				}

				// bracketify
				replace       = '(' + replace + ')';
				parsedFormula = parsedFormula.replace(fullMatch, replace);
			}

			return parsedFormula;
		},


		get_calculation_field: function (element_id) {
			for (var i = 0; i < this.calculationFields.length; i++) {
				if(this.calculationFields[i].name === element_id) {
					return this.calculationFields[i];
				}
			}

			return false;
		},

		is_hidden: function (element_id) {
			var $element_id = this.get_form_field(element_id),
				$column_field = $element_id.closest('.forminator-col'),
				$row_field = $column_field.closest('.forminator-row')
			;

			if( $row_field.hasClass("forminator-hidden-option") || $column_field.hasClass("forminator-hidden-option") ) {
				return false;
			}

			if( $row_field.hasClass("forminator-hidden") || $column_field.hasClass("forminator-hidden") ) {
				return true;
			}

			return false;
		},

		get_field_value: function (element_id) {
			var $element    = this.get_form_field(element_id);
			var value       = 0;
			var calculation = 0;
			var checked     = null;

			if (this.field_is_radio($element)) {
				checked = $element.filter(":checked");
				if (checked.length) {
					calculation = checked.data('calculation');
					if (calculation !== undefined) {
						value = Number(calculation);
					}
				}
			} else if (this.field_is_checkbox($element)) {
				$element.each(function () {
					if ($(this).is(':checked')) {
						calculation = $(this).data('calculation');
						if (calculation !== undefined) {
							value += Number(calculation);
						}
					}
				});

			} else if (this.field_is_select($element)) {
				checked = $element.find("option").filter(':selected');
				if (checked.length) {
					calculation = checked.data('calculation');
					if (calculation !== undefined) {
						value = Number(calculation);
					}
				}
			} else if ( this.field_has_inputMask( $element ) ) {
				value = parseFloat( $element.inputmask( 'unmaskedvalue' ) );
			} else {
				var number = $element.val();
				value = parseFloat( number.replace(',','.') );
			}

			return isNaN(value) ? 0 : value;
		},

		field_has_inputMask: function ( $element ) {
			var hasMask = false;

			$element.each(function () {
				if ( undefined !== $( this ).attr( 'data-inputmask' ) ) {
					hasMask = true;
					//break
					return false;
				}
			});

			return hasMask;
		},

		field_is_radio: function ($element) {
			var is_radio = false;
			$element.each(function () {
				if ($(this).attr('type') === 'radio') {
					is_radio = true;
					//break
					return false;
				}
			});

			return is_radio;
		},

		field_is_checkbox: function ($element) {
			var is_checkbox = false;
			$element.each(function () {
				if ($(this).attr('type') === 'checkbox') {
					is_checkbox = true;
					//break
					return false;
				}
			});

			return is_checkbox;
		},

		field_is_select: function ($element) {
			return $element.is('select');
		},

		displayErrorMessage: function ($element, errorMessage) {
			var $field_holder = $element.closest('.forminator-field--inner');

			if ($field_holder.length === 0) {
				$field_holder = $element.closest('.forminator-field');
			}

			var $error_holder = $field_holder.find('.forminator-error-message');

			if ($error_holder.length === 0) {
				$field_holder.append('<span class="forminator-error-message" aria-hidden="true"></span>');
				$error_holder = $field_holder.find('.forminator-error-message');
			}

			$element.attr('aria-invalid', 'true');
			$error_holder.html(errorMessage);
			$field_holder.addClass('forminator-has_error');
		},

		hideErrorMessage: function ($element) {
			var $field_holder = $element.closest('.forminator-field--inner');

			if ($field_holder.length === 0) {
				$field_holder = $element.closest('.forminator-field');
			}

			var $error_holder = $field_holder.find('.forminator-error-message');

			$element.removeAttr('aria-invalid');
			$error_holder.remove();
			$field_holder.removeClass('forminator-has_error');
		},

	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontCalculate(this, options));
			}
		});
	};

})(jQuery, window, document);

// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "forminatorFrontMergeTags",
	    defaults   = {
		    print_value: false,
		    forminatorFields: [],
	    };

	// The actual plugin constructor
	function forminatorFrontMergeTags(element, options) {
		this.element = element;
		this.$el     = $(this.element);

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings          = $.extend({}, defaults, options);
		this._defaults         = defaults;
		this._name             = pluginName;
		this.formFields        = [];
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(forminatorFrontMergeTags.prototype, {
		init: function () {
			var self = this;
			var fields = this.$el.find('.forminator-merge-tags');

			if (fields.length > 0) {
				fields.each(function () {
					self.formFields.push({
						$input: $(this),
						value: $(this).html(),
					});
				});
			}

			this.replaceAll();
			this.attachEvents();
		},

		attachEvents: function () {
			var self = this;

			this.$el.find(
				'.forminator-textarea, input.forminator-input, .forminator-checkbox, .forminator-radio, .forminator-input-file, select.forminator-select2, .forminator-multiselect input'
			).each(function () {
				$(this).on('change', function () {
					// Give jquery sometime to apply changes
					setTimeout( function() {
					   self.replaceAll();
               }, 300 );
				});
			});
		},

		replaceAll: function () {
			for (var i = 0; i < this.formFields.length; i++) {
				this.replace(this.formFields[i]);
			}
		},

		replace: function ( field ) {
			var $input = field.$input;
			var res = this.maybeReplaceValue(field.value);

			$input.html(res);
		},

		maybeReplaceValue: function (value) {
			var joinedFieldTypes      = this.settings.forminatorFields.join('|');
			var incrementFieldPattern = "(" + joinedFieldTypes + ")-\\d+";
			var pattern               = new RegExp('\\{(' + incrementFieldPattern + ')(\\-[A-Za-z-_]+)?\\}', 'g');
			var parsedValue           = value;

			var matches;
			while (matches = pattern.exec(value)) {
				var fullMatch = matches[0];
				var inputName = fullMatch.replace('{', '').replace('}', '');
				var fieldType = matches[2];

				var replace = fullMatch;

				if (fullMatch === undefined || inputName === undefined || fieldType === undefined) {
					continue;
				}

				replace = this.get_field_value(inputName);

				parsedValue = parsedValue.replace(fullMatch, replace);
			}

			return parsedValue;
		},

		// taken from forminatorFrontCondition
		get_form_field: function (element_id) {
			//find element by suffix -field on id input (default behavior)
			var $element = this.$el.find('#' + element_id + '-field');
			if ($element.length === 0) {
				//find element by its on name
				$element = this.$el.find('[name=' + element_id + ']');
				if ($element.length === 0) {
					//find element by its on name[] (for checkbox on multivalue)
					$element = this.$el.find('input[name="' + element_id + '[]"]');
					if ($element.length === 0) {
						//find element by direct id (for name field mostly)
						//will work for all field with element_id-[somestring]
						$element = this.$el.find('#' + element_id);
					}
				}
			}

			return $element;
		},

		is_calculation: function (element_id) {
			var $element    = this.get_form_field(element_id);

			if ( $element.hasClass("forminator-calculation") ) {
				return true;
			}

			return false;
		},

		get_field_value: function (element_id) {
			var $element    = this.get_form_field(element_id),
				self        = this,
				value       = '',
				checked     = null;

			if ( this.is_hidden( element_id ) && ! this.is_calculation( element_id ) ) {
         	return '';
			}

			if ( this.is_calculation( element_id ) ) {
				var $element_id = this.get_form_field(element_id),
					$column_field = $element_id.closest('.forminator-col'),
					$row_field = $column_field.closest('.forminator-row')
				;

				if ( ! $row_field.hasClass("forminator-hidden-option") && this.is_hidden( element_id ) ) {
					return '';
				}
			}

			if (this.field_is_radio($element)) {
				checked = $element.filter(":checked");

				if (checked.length) {
					if ( this.settings.print_value ) {
						value = checked.val();
					} else {
						value = 0 === checked.siblings( '.forminator-radio-label' ).length
								? checked.siblings( '.forminator-screen-reader-only' ).text()
								: checked.siblings( '.forminator-radio-label' ).text();
					}
				}
			} else if (this.field_is_checkbox($element)) {
				$element.each(function () {
					if ($(this).is(':checked')) {
						if(value !== "") {
							value += ', ';
						}

						var multiselect = !! $(this).closest('.forminator-multiselect').length;

						if ( self.settings.print_value ) {
							value += $(this).val();
						} else if ( multiselect ) {
							value += $(this).closest('label').text();
						} else {
							value += 0 === $(this).siblings( '.forminator-checkbox-label' ).length
									 ? $(this).siblings( '.forminator-screen-reader-only' ).text()
									 : $(this).siblings( '.forminator-checkbox-label' ).text();
						}
					}
				});

			} else if (this.field_is_select($element)) {
				checked = $element.find("option").filter(':selected');
				if (checked.length) {
					if ( this.settings.print_value ) {
						value = checked.val();
					} else {
						value = checked.text();
					}
				}
			} else if (this.field_is_upload($element)) {
				value = $element.val().split('\\').pop();
			} else if (this.field_has_inputMask($element)) {
				$element.inputmask({'autoUnmask' : false});
				value = $element.val();
				$element.inputmask({'autoUnmask' : true});
			} else {
				value = $element.val();
			}

			return value;
		},

		field_has_inputMask: function ( $element ) {
			var hasMask = false;

			$element.each(function () {
				if ( undefined !== $( this ).attr( 'data-inputmask' ) ) {
					hasMask = true;
					//break
					return false;
				}
			});

			return hasMask;
		},

		field_is_radio: function ($element) {
			var is_radio = false;
			$element.each(function () {
				if ($(this).attr('type') === 'radio') {
					is_radio = true;
					//break
					return false;
				}
			});

			return is_radio;
		},

		field_is_checkbox: function ($element) {
			var is_checkbox = false;
			$element.each(function () {
				if ($(this).attr('type') === 'checkbox') {
					is_checkbox = true;
					//break
					return false;
				}
			});

			return is_checkbox;
		},

		field_is_upload: function ($element) {
			if ($element.attr('type') === 'file') {
				return true;
			}

			return false;
		},

		field_is_select: function ($element) {
			return $element.is('select');
		},

		// modified from front.condition
		is_hidden: function (element_id) {
			var $element_id = this.get_form_field(element_id),
				$column_field = $element_id.closest('.forminator-col'),
				$row_field = $column_field.closest('.forminator-row')
			;

			if ( $row_field.hasClass("forminator-hidden-option") || $row_field.hasClass("forminator-hidden") ) {
				return true;
			}

			if( $column_field.hasClass("forminator-hidden") ) {
				return true;
			}

			return false;
		},
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new forminatorFrontMergeTags(this, options));
			}
		});
	};

})(jQuery, window, document);

// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// Polyfill
	if (!Object.assign) {
		Object.defineProperty(Object, 'assign', {
			enumerable: false,
			configurable: true,
			writable: true,
			value: function(target, firstSource) {
				'use strict';
				if (target === undefined || target === null) {
					throw new TypeError('Cannot convert first argument to object');
				}

				var to = Object(target);
				for (var i = 1; i < arguments.length; i++) {
					var nextSource = arguments[i];
					if (nextSource === undefined || nextSource === null) {
						continue;
					}

					var keysArray = Object.keys(Object(nextSource));
					for (var nextIndex = 0, len = keysArray.length; nextIndex < len; nextIndex++) {
						var nextKey = keysArray[nextIndex];
						var desc = Object.getOwnPropertyDescriptor(nextSource, nextKey);
						if (desc !== undefined && desc.enumerable) {
							to[nextKey] = nextSource[nextKey];
						}
					}
				}
				return to;
			}
		});
	}

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "forminatorFrontPayment",
	    defaults   = {
		    type: 'stripe',
		    paymentEl: null,
		    paymentRequireSsl: false,
		    generalMessages: {},
	    };

	// The actual plugin constructor
	function ForminatorFrontPayment(element, options) {
		this.element = element;
		this.$el     = $(this.element);

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings              = $.extend({}, defaults, options);
		this._defaults             = defaults;
		this._name                 = pluginName;
		this._stripeData           = null;
		this._stripe			   = null;
		this._cardElement          = null;
		this._stripeToken		   = null;
		this._beforeSubmitCallback = null;
		this._form                 = null;
		this._paymentIntent        = null;
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFrontPayment.prototype, {
		init: function () {
			if (!this.settings.paymentEl || typeof this.settings.paymentEl.data() === 'undefined') {
				return;
			}

			var self         = this;
			this._stripeData = this.settings.paymentEl.data();

			if ( false === this.mountCardField() ) {
				return;
			}

			$(this.element).on('payment.before.submit.forminator', function (e, formData, callback) {
				self._form = self.getForm(e);
				self._beforeSubmitCallback = callback;
				self.validateStripe(e, formData);
			});

			this.$el.on("forminator:form:submit:stripe:3dsecurity", function(e, secret, subscription) {
				self.validate3d(e, secret, subscription);
			});

			// Listen for fields change to update ZIP mapping
			this.$el.find(
				'input.forminator-input, .forminator-checkbox, .forminator-radio, select.forminator-select2'
			).each(function () {
				$(this).on('change', function (e) {
					self.mapZip(e);
				});
			});
		},

		validate3d: function( e, secret, subscription ) {
			var self = this;

			if ( subscription ) {
				this._stripe.confirmCardPayment(secret, {
					payment_method: {
						card: self._cardElement,
						...self.getBillingData(),
					},
				})
				.then(function(result) {
					self.$el.find('#forminator-stripe-subscriptionid').val( subscription );

					if (self._beforeSubmitCallback) {
						self._beforeSubmitCallback.call();
					}
				});
			} else {
				this._stripe.retrievePaymentIntent(
					secret
				).then(function(result) {
					if ( result.paymentIntent.status === 'requires_action' ||  result.paymentIntent.status === 'requires_source_action' ) {
						self._stripe.handleCardAction(
							secret
						).then(function(result) {
							if (self._beforeSubmitCallback) {
								self._beforeSubmitCallback.call();
							}
						});
					}
				});
			}
		},

		validateStripe: function(e, formData) {
			var self = this;

			this._stripe.createToken(this._cardElement).then(function (result) {
				if (result.error) {
					self.showCardError(result.error.message, true);
					self.$el.find( 'button' ).removeAttr( 'disabled' );
				} else {
					self.hideCardError();

					self._stripe.createPaymentMethod('card', self._cardElement, self.getBillingData()).then(function (result) {
						var paymentMethod = self.getObjectValue(result, 'paymentMethod');

						self._stripeData['paymentMethod'] = self.getObjectValue(paymentMethod, 'id');
						self.updateAmount(e, formData, result);
					});
				}
			});
		},

		isValid: function(focus) {
			var self = this;

			this._stripe.createToken(this._cardElement).then(function (result) {
				if (result.error) {
					self.showCardError(result.error.message, focus);
				} else {
					self.hideCardError();
				}
			});
		},

		getForm: function(e) {
			var $form = $( e.target );

			if(!$form.hasClass('forminator-custom-form')) {
				$form = $form.closest('form.forminator-custom-form');
			}

			return $form;
		},

		updateAmount: function(e, formData, result) {
			e.preventDefault();
			var self = this;
			var updateFormData = formData;
			var paymentMethod = this.getObjectValue(result, 'paymentMethod');

			//Method set() doesn't work in IE11
			updateFormData.append( 'action', 'forminator_update_payment_amount' );
			updateFormData.append( 'paymentid', this.getStripeData('paymentid') );
			updateFormData.append( 'payment_method', this.getObjectValue(paymentMethod, 'id') );

			var receipt = this.getStripeData('receipt');
			var receiptEmail = this.getStripeData('receiptEmail');
			var receiptObject = {};

			if( receipt && receiptEmail ) {
				var emailValue = this.get_field_value(receiptEmail) || '';

				updateFormData.append( 'receipt_email', emailValue );
			}

			$.ajax({
				type: 'POST',
				url: window.ForminatorFront.ajaxUrl,
				data: updateFormData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend: function () {
					if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
						// Disable form fields
						self._form.addClass('forminator-fields-disabled');

						var $target_message = self._form.find('.forminator-response-message');

						$target_message.html('<p>' + self.settings.loader_label + '</p>');

						self.focus_to_element($target_message);

						$target_message.removeAttr("aria-hidden")
							.prop("tabindex", "-1")
							.removeClass('forminator-success forminator-error')
							.addClass('forminator-loading forminator-show');
					}

					self._form.find('button').attr('disabled', true);
				},
				success: function (data) {
					if (data.success === true) {
						// Store payment id
						if (typeof data.data !== 'undefined' && typeof data.data.paymentid !== 'undefined') {
							self.$el.find('#forminator-stripe-paymentid').val(data.data.paymentid);
							self.$el.find('#forminator-stripe-paymentmethod').val(self._stripeData['paymentMethod']);
							self._stripeData['paymentid'] = data.data.paymentid;
							self._stripeData['secret'] = data.data.paymentsecret;

							self.handleCardPayment(data, e, formData);
						} else {
							self.show_error('Invalid Payment Intent ID');
						}
					} else {
						self.show_error(data.data.message);

						if(data.data.errors.length) {
							self.show_messages(data.data.errors);
						}

						var $captcha_field = self._form.find('.forminator-g-recaptcha');

						if ($captcha_field.length) {
							$captcha_field = $($captcha_field.get(0));

							var recaptcha_widget = $captcha_field.data('forminator-recapchta-widget'),
								recaptcha_size = $captcha_field.data('size');

							if (recaptcha_size === 'invisible') {
								window.grecaptcha.reset(recaptcha_widget);
							}
						}
					}
				},
				error: function (err) {
					var $message = err.status === 400 ? window.ForminatorFront.cform.upload_error : window.ForminatorFront.cform.error;

					self.show_error($message);
				}
			})
		},

		show_error: function(message) {
			var $target_message = this._form.find('.forminator-response-message');

			this._form.find('button').removeAttr('disabled');

			$target_message.removeAttr("aria-hidden")
				.prop("tabindex", "-1")
				.removeClass('forminator-loading')
				.addClass('forminator-error forminator-show');

			$target_message.html('<p>' + message + '</p>');

			this.focus_to_element($target_message);

			this.enable_form();
		},

		enable_form: function() {
			if( typeof this.settings.has_loader !== "undefined" && this.settings.has_loader ) {
				var $target_message = this._form.find('.forminator-response-message');

				// Enable form fields
				this._form.removeClass('forminator-fields-disabled');

				$target_message.removeClass('forminator-loading');
			}
		},

		mapZip: function (e) {
			var verifyZip = this.getStripeData('veifyZip');
			var zipField = this.getStripeData('zipField');
			var changedField = $(e.currentTarget).attr('name');

			// Verify ZIP is enabled, mapped field is not empty and changed field is the mapped field, proceed
			if (verifyZip && zipField !== "" && changedField === zipField) {
				if (e.originalEvent !== undefined) {
					// Get field
					var value = this.get_field_value(zipField);

					// Update card element
					this._cardElement.update({
						value: {
							postalCode: value
						}
					});
				}
			}
		},

		focus_to_element: function ($element) {
			// force show in case its hidden of fadeOut
			$element.show();
			$('html,body').animate({scrollTop: ($element.offset().top - ($(window).height() - $element.outerHeight(true)) / 2)}, 500, function () {
				if (!$element.attr("tabindex")) {
					$element.attr("tabindex", -1);
				}

				$element.focus();
			});
		},

		show_messages: function (errors) {
			var self = this,
				forminatorFrontCondition = self.$el.data('forminatorFrontCondition');
			if (typeof forminatorFrontCondition !== 'undefined') {
				// clear all validation message before show new one
				this.$el.find('.forminator-error-message').remove();
				var i = 0;
				errors.forEach(function (value) {
					var element_id = Object.keys(value),
						message = Object.values(value),
						element = forminatorFrontCondition.get_form_field(element_id);
					if (element.length) {
						if (i === 0) {
							// focus on first error
							self.$el.trigger('forminator.front.pagination.focus.input',[element]);
							self.focus_to_element(element);
						}

						if ($(element).hasClass('forminator-input-time')) {
							var $time_field_holder = $(element).closest('.forminator-field:not(.forminator-field--inner)'),
								$time_error_holder = $time_field_holder.children('.forminator-error-message');

							if ($time_error_holder.length === 0) {
								$time_field_holder.append('<span class="forminator-error-message" aria-hidden="true"></span>');
								$time_error_holder = $time_field_holder.children('.forminator-error-message');
							}
							$time_error_holder.html(message);
						}

						var $field_holder = $(element).closest('.forminator-field--inner');

						if ($field_holder.length === 0) {
							$field_holder = $(element).closest('.forminator-field');
							if ($field_holder.length === 0) {
								// handling postdata field
								$field_holder = $(element).find('.forminator-field');
								if ($field_holder.length > 1) {
									$field_holder = $field_holder.first();
								}
							}
						}

						var $error_holder = $field_holder.find('.forminator-error-message');

						if ($error_holder.length === 0) {
							$field_holder.append('<span class="forminator-error-message" aria-hidden="true"></span>');
							$error_holder = $field_holder.find('.forminator-error-message');
						}
						$(element).attr('aria-invalid', 'true');
						$error_holder.html(message);
						$field_holder.addClass('forminator-has_error');
						i++;
					}
				});
			}

			return this;
		},

		getBillingData: function (formData) {
			var billing = this.getStripeData('billing');

			// If billing is disabled, return
			if (!billing) {
				return {}
			};

			// Get billing fields
			var billingName = this.getStripeData('billingName');
			var billingEmail = this.getStripeData('billingEmail');
			var billingAddress = this.getStripeData('billingAddress');

			// Create billing object
			var billingObject = {
				address: {}
			}

			if( billingName ) {
				var nameField = this.get_field_value(billingName);

				// Check if Name field is multiple
				if (!nameField) {
					var fName = this.get_field_value(billingName + '-first-name') || '';
					var lName = this.get_field_value(billingName + '-last-name') || '';

					nameField = fName + ' ' + lName;
				}

				// Check if Name field is empty in the end, if not assign to the object
				if (nameField) {
					billingObject.name = nameField;
				}
			}

			// Map email field
			if(billingEmail) {
				var billingEmailValue = this.get_field_value(billingEmail) || '';
				if (billingEmailValue) {
					billingObject.email = billingEmailValue;
				}
			}

			// Map address line 1 field
			var addressLine1 = this.get_field_value(billingAddress + '-street_address') || '';
			if (addressLine1) {
				billingObject.address.line1 = addressLine1;
			}

			// Map address line 2 field
			var addressLine2 = this.get_field_value(billingAddress + '-address_line') || '';
			if (addressLine2) {
				billingObject.address.line2 = addressLine2;
			}

			// Map address city field
			var addressCity = this.get_field_value(billingAddress + '-city') || '';
			if (addressCity) {
				billingObject.address.city = addressCity;
			}

			// Map address state field
			var addressState = this.get_field_value(billingAddress + '-state') || '';
			if (addressState) {
				billingObject.address.state = addressState;
			}

			// Map address country field
			var countryField = this.get_form_field(billingAddress + '-country');
			var addressCountry = countryField.find(':selected').data('country-code');

			if (addressCountry) {
				billingObject.address.country = addressCountry;
			}

			// Map address country field
			var addressZip = this.get_field_value(billingAddress + '-zip') || '';
				if (addressZip) {
				billingObject.address.postal_code = addressZip;
			}

			return {
				billing_details: billingObject
			}
		},

		handleCardPayment: function (data, e, formData) {
			var self = this,
				secret = data.data.paymentsecret || false,
				input = $( '.forminator-number--field, .forminator-currency, .forminator-calculation' );

			if ( input.inputmask ) {
				input.inputmask('remove');
			}

			if (self._beforeSubmitCallback) {
				self._beforeSubmitCallback.call();
			}
		},

		mountCardField: function () {
			var key = this.getStripeData('key');
			var cardIcon = this.getStripeData('cardIcon');
			var verifyZip = this.getStripeData('veifyZip');
			var zipField = this.getStripeData('zipField');
			var fieldId = this.getStripeData('fieldId');

			if ( null === key ) {
				return false;
			}

			// Init Stripe
			this._stripe = Stripe( key, {
				locale: this.getStripeData('language')
			} );

			// Create empty ZIP object
			var zipObject = {}

			if (!verifyZip) {
				// If verify ZIP is disabled, disable ZIP
				zipObject.hidePostalCode = true;
			} else {
				// Set empty post code, later will be updated when field is changed
				zipObject.value = {
					postalCode: '',
				};
			}

			var stripeObject = {};
			var fontFamily = this.getStripeData('fontFamily');
			var customFonts = this.getStripeData('customFonts');
			if (fontFamily && customFonts) {
				stripeObject.fonts = [
					{
						cssSrc: 'https://fonts.googleapis.com/css?family=' + fontFamily,
					}
				];
			}

			var elements = this._stripe.elements(stripeObject);

			this._cardElement = elements.create('card', Object.assign(
				{
					classes: {
						base: this.getStripeData('baseClass'),
						complete: this.getStripeData('completeClass'),
						empty: this.getStripeData('emptyClass'),
						focus: this.getStripeData('focusedClass'),
						invalid: this.getStripeData('invalidClass'),
						webkitAutofill: this.getStripeData('autofilledClass'),
					},
					style: {
						base: {
							iconColor: this.getStripeData( 'iconColor' ),
							color: this.getStripeData( 'fontColor' ),
							lineHeight: this.getStripeData( 'lineHeight' ),
							fontWeight: this.getStripeData( 'fontWeight' ),
							fontFamily: this.getStripeData( 'fontFamily' ),
							fontSmoothing: 'antialiased',
							fontSize: this.getStripeData( 'fontSize' ),
							'::placeholder': {
								color: this.getStripeData( 'placeholder' ),
							},
							':hover': {
								iconColor: this.getStripeData( 'iconColorHover' ),
							},
							':focus': {
								iconColor: this.getStripeData( 'iconColorFocus' ),
							}
						},
						invalid: {
							iconColor: this.getStripeData( 'iconColorError' ),
							color: this.getStripeData( 'fontColorError' ),
						},
					},
					iconStyle: 'solid',
					hideIcon: !cardIcon,
				},
				zipObject
			));
			this._cardElement.mount('#card-element-' + fieldId);
			this.validateCard();
		},

		validateCard: function () {
			var self = this;
			this._cardElement.on( 'change', function( event ) {
				if ( self.$el.find( '.forminator-stripe-element' ).hasClass( 'StripeElement--empty' ) ) {
					self.$el.find( '.forminator-stripe-element' ).closest( '.forminator-field' ).removeClass( 'forminator-is_filled' );
				} else {
					self.$el.find( '.forminator-stripe-element' ).closest( '.forminator-field' ).addClass( 'forminator-is_filled' );
				}

				if ( self.$el.find( '.forminator-stripe-element' ).hasClass( 'StripeElement--invalid' ) ) {
					self.$el.find( '.forminator-stripe-element' ).closest( '.forminator-field' ).addClass( 'forminator-has_error' );
				}
			});

			this._cardElement.on('focus', function(event) {
				self.$el.find('.forminator-stripe-element').closest('.forminator-field').addClass('forminator-is_active');
			});

			this._cardElement.on('blur', function(event) {
				self.$el.find('.forminator-stripe-element').closest('.forminator-field').removeClass('forminator-is_active');

				self.isValid(false);
			});
		},

		hideCardError: function () {
			var $field_holder = this.$el.find('.forminator-card-message');
			var $error_holder = $field_holder.find('.forminator-error-message');

			if ($error_holder.length === 0) {
				$field_holder.append('<span class="forminator-error-message" aria-hidden="true"></span>');
				$error_holder = $field_holder.find('.forminator-error-message');
			}

			$field_holder.closest('.forminator-field').removeClass('forminator-has_error');
			$error_holder.html('');
		},

		showCardError: function (message, focus) {
			var $field_holder = this.$el.find('.forminator-card-message');
			var $error_holder = $field_holder.find('.forminator-error-message');

			if ($error_holder.length === 0) {
				$field_holder.append('<span class="forminator-error-message" aria-hidden="true"></span>');
				$error_holder = $field_holder.find('.forminator-error-message');
			}

			$field_holder.closest('.forminator-field').addClass('forminator-has_error');
			$field_holder.closest('.forminator-field').addClass( 'forminator-is_filled' );
			$error_holder.html(message);

			if(focus) {
				this.focus_to_element($field_holder.closest('.forminator-field'));
			}
		},

		getStripeData: function (key) {
			if ( (typeof this._stripeData !== 'undefined') && (typeof this._stripeData[key] !== 'undefined') ) {
				return this._stripeData[key];
			}

			return null;
		},

		getObjectValue: function(object, key) {
			if (typeof object[key] !== 'undefined') {
				return object[key];
			}

			return null;
		},

		// taken from forminatorFrontCondition
		get_form_field: function (element_id) {
			//find element by suffix -field on id input (default behavior)
			var $element = this.$el.find('#' + element_id + '-field');
			if ($element.length === 0) {
				//find element by its on name (for radio on singlevalue)
				$element = this.$el.find('input[name=' + element_id + ']');
				if ($element.length === 0) {
					// for text area that have uniqid, so we check its name instead
					$element = this.$el.find('textarea[name=' + element_id + ']');
					if ($element.length === 0) {
						//find element by its on name[] (for checkbox on multivalue)
						$element = this.$el.find('input[name="' + element_id + '[]"]');
						if ($element.length === 0) {
							//find element by direct id (for name field mostly)
							//will work for all field with element_id-[somestring]
							$element = this.$el.find('#' + element_id);
						}
					}
				}
			}

			return $element;
		},

		get_field_value: function (element_id) {
			var $element = this.get_form_field(element_id);
			var value    = '';
			var checked  = null;

			if (this.field_is_radio($element)) {
				checked = $element.filter(":checked");
				if (checked.length) {
					value = checked.val();
				}
			} else if (this.field_is_checkbox($element)) {
				$element.each(function () {
					if ($(this).is(':checked')) {
						value = $(this).val();
					}
				});

			} else if (this.field_is_select($element)) {
				value = $element.val();
			} else if ( this.field_has_inputMask( $element ) ) {
				value = parseFloat( $element.inputmask( 'unmaskedvalue' ) );
			} else {
				value = $element.val()
			}

			return value;
		},

		get_field_calculation: function (element_id) {
			var $element    = this.get_form_field(element_id);
			var value       = 0;
			var calculation = 0;
			var checked     = null;

			if (this.field_is_radio($element)) {
				checked = $element.filter(":checked");
				if (checked.length) {
					calculation = checked.data('calculation');
					if (calculation !== undefined) {
						value = Number(calculation);
					}
				}
			} else if (this.field_is_checkbox($element)) {
				$element.each(function () {
					if ($(this).is(':checked')) {
						calculation = $(this).data('calculation');
						if (calculation !== undefined) {
							value += Number(calculation);
						}
					}
				});

			} else if (this.field_is_select($element)) {
				checked = $element.find("option").filter(':selected');
				if (checked.length) {
					calculation = checked.data('calculation');
					if (calculation !== undefined) {
						value = Number(calculation);
					}
				}
			} else {
				value = Number($element.val());
			}

			return isNaN(value) ? 0 : value;
		},

		field_has_inputMask: function ( $element ) {
			var hasMask = false;

			$element.each(function () {
				if ( undefined !== $( this ).attr( 'data-inputmask' ) ) {
					hasMask = true;
					//break
					return false;
				}
			});

			return hasMask;
		},

		field_is_radio: function ($element) {
			var is_radio = false;
			$element.each(function () {
				if ($(this).attr('type') === 'radio') {
					is_radio = true;
					//break
					return false;
				}
			});

			return is_radio;
		},

		field_is_checkbox: function ($element) {
			var is_checkbox = false;
			$element.each(function () {
				if ($(this).attr('type') === 'checkbox') {
					is_checkbox = true;
					//break
					return false;
				}
			});

			return is_checkbox;
		},

		field_is_select: function ($element) {
			return $element.is('select');
		},
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontPayment(this, options));
			}
		});
	};

})(jQuery, window, document);

// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "forminatorFrontPagination",
		defaults = {
			totalSteps: 0,
			step: 0,
			hashStep: 0,
			inline_validation: false
		};

	// The actual plugin constructor
	function ForminatorFrontPagination(element, options) {
		this.element = $(element);
		this.$el = this.element;
		this.totalSteps = 0;
		this.step = 0;
		this.finished = false;
		this.hashStep = false;
		this.next_button_txt = '';
		this.prev_button_txt = '';
		this.custom_label = [];
		this.form_id = 0;
		this.element = '';

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFrontPagination.prototype, {
		init: function () {
			var self = this;
			var draftPage = !! this.$el.data( 'draft-page' ) ? this.$el.data( 'draft-page' ) : 0;

			this.next_button = this.settings.next_button ? this.settings.next_button : window.ForminatorFront.cform.pagination_next;
			this.prev_button = this.settings.prev_button ? this.settings.prev_button : window.ForminatorFront.cform.pagination_prev;

			if (this.$el.find('input[name=form_id]').length > 0) {
				this.form_id = this.$el.find('input[name=form_id]').val();
			}

			this.totalSteps = this.settings.totalSteps;
			this.step = this.settings.step;
			this.quiz = this.settings.quiz;
			this.element = this.$el.find('[data-step=' + this.step + ']').data('name');
			if (this.form_id && typeof window.Forminator_Cform_Paginations === 'object' && typeof window.Forminator_Cform_Paginations[this.form_id] === 'object') {
				this.custom_label = window.Forminator_Cform_Paginations[this.form_id];
			}

			if ( draftPage > 0 ) {
				this.go_to( draftPage, true );
			} else if (this.settings.hashStep && this.step > 0) {
				this.go_to(this.step, true);
			} else if ( this.quiz ) {
				this.go_to(0, true);
			} else {
				this.go_to(0, false);
			}

			this.render_navigation();
			this.render_bar_navigation();
			this.render_footer_navigation( this.form_id );
			this.init_events();
			this.update_buttons();
			this.update_navigation();

			this.$el.find('.forminator-button.forminator-button-back, .forminator-button.forminator-button-next, .forminator-button.forminator-button-submit').on("click", function (e) {
				e.preventDefault();
				$(this).trigger('forminator.front.pagination.move');
				self.resetRichTextEditorHeight();
			});

			this.$el.on('click', '.forminator-result--view-answers', function(e){
				e.preventDefault();
				$(this).trigger('forminator.front.pagination.move');
			});

		},
		init_events: function () {
			var self = this;

			this.$el.find('.forminator-button-back').on('forminator.front.pagination.move',function (e) {
				self.handle_click('prev');
			});
			this.$el.on('forminator.front.pagination.move', '.forminator-result--view-answers', function (e) {
				self.handle_click('prev');
			});
			this.$el.find('.forminator-button-next').on('forminator.front.pagination.move', function (e) {
				self.handle_click('next');
			});

			this.$el.find('.forminator-step').on("click", function (e) {
				e.preventDefault();
				var step = $(this).data('nav');
				self.handle_step(step);
			});

			this.$el.on('reset', function (e) {
				self.on_form_reset(e);
			});

			this.$el.on('forminator:quiz:submit:success', function (e, ajaxData, formData, resultText) {
				if ( resultText ) {
					self.move_to_results(e);
				}
			});

			this.$el.on('forminator.front.pagination.focus.input', function (e, input) {
				self.on_focus_input(e, input);
			});

		},

		/**
		 * Move quiz to rezult page
		 */
		move_to_results: function (e) {
			this.finished = true;
			if ( this.$el.find('.forminator-submit-rightaway').length ) {
				this.$el.find('#forminator-submit').removeClass('forminator-hidden');
			} else {
				this.handle_click('next');
			}
		},

		/**
		 * On reset event of Form
		 *
		 * @since 1.0.3
		 *
		 * @param e
		 */
		on_form_reset: function (e) {
			// Trigger pagination to first page
			this.go_to(0, true);
			this.update_buttons();
		},

		/**
		 * On Input focused
		 *
		 * @param e
		 * @param input
		 */
		on_focus_input: function (e, input) {
			//Go to page where element exist
			var step = this.get_page_of_input(input);
			this.go_to(step, true);
			this.update_buttons();
		},
		render_footer_navigation: function( form_id ) {
			var footer_html = '',
				paypal_field = '',
				footer_align = ( this.custom_label['has-paypal'] === true ) ? ' style="align-items: flex-start;"' : '',
				save_draft_btn = this.$el.find( '.forminator-save-draft-link' ).length ? this.$el.find( '.forminator-save-draft-link' ) : ''
				;

			if ( this.custom_label[ this.element ] && this.custom_label[ 'pagination-labels' ] === 'custom' ){
				this.prev_button_txt = this.custom_label[ this.element ][ 'prev-text' ] !== '' ? this.custom_label[ this.element ][ 'prev-text' ] : this.prev_button;
				this.next_button_txt = this.custom_label[ this.element ][ 'next-text' ] !== '' ? this.custom_label[ this.element ][ 'next-text' ] : this.next_button;
			} else {
				this.prev_button_txt = this.prev_button;
				this.next_button_txt = this.next_button;
			}

			if ( this.$el.hasClass('forminator-design--material') ) {
				footer_html = '<div class="forminator-pagination-footer"' + footer_align + '>' +
					'<button class="forminator-button forminator-button-back"><span class="forminator-button--mask" aria-label="hidden"></span><span class="forminator-button--text">' + this.prev_button_txt + '</span></button>' +
					'<button class="forminator-button forminator-button-next"><span class="forminator-button--mask" aria-label="hidden"></span><span class="forminator-button--text">' + this.next_button_txt + '</span></button>';
				if( this.custom_label[ 'has-paypal' ] === true ) {
					paypal_field = ( this.custom_label['paypal-id'] ) ? this.custom_label['paypal-id'] : '';
					footer_html += '<div class="forminator-payment forminator-button-paypal forminator-hidden ' + paypal_field + '-payment" id="paypal-button-container-' + form_id + '">';
				}
				footer_html += '</div>';
				this.$el.append( footer_html );

			} else {
				footer_html = '<div class="forminator-pagination-footer"' + footer_align + '>' +
					'<button class="forminator-button forminator-button-back">' + this.prev_button_txt + '</button>' +
					'<button class="forminator-button forminator-button-next">' + this.next_button_txt + '</button>';
				if( this.custom_label['has-paypal'] === true ) {
					paypal_field = ( this.custom_label['paypal-id'] ) ? this.custom_label['paypal-id'] : '';
					footer_html += '<div class="forminator-payment forminator-button-paypal forminator-hidden ' + paypal_field + '-payment" id="paypal-button-container-' + form_id + '">';
				}
				footer_html += '</div>';
				this.$el.append( footer_html );

			}

			if ( '' !== save_draft_btn ) {
				save_draft_btn.insertBefore( '.forminator-button-next' );
			}

		},

		render_bar_navigation: function () {

			var $navigation = this.$el.find( '.forminator-pagination-progress' );

			var $progressLabel = '<div class="forminator-progress-label">0%</div>',
				$progressBar   = '<div class="forminator-progress-bar"><span style="width: 0%"></span></div>'
			;

			if ( ! $navigation.length ) return;

			$navigation.html( $progressLabel + $progressBar );

			this.calculate_bar_percentage();

		},

		calculate_bar_percentage: function () {

			var total     = this.totalSteps,
				current   = this.step + 1,
				$progress = this.$el
			;

			if ( ! $progress.length ) return;

			var percentage = Math.round( (current / total) * 100 );

			$progress.find( '.forminator-progress-label' ).html( percentage + '%' );
			$progress.find( '.forminator-progress-bar span' ).css( 'width', percentage + '%' );

		},

		render_navigation: function () {
			var $navigation = this.$el.find('.forminator-pagination-steps');

			var finalSteps = this.$el.find('.forminator-pagination-start');

			if ( ! $navigation.length ) return;

			var steps = this.$el.find( '.forminator-pagination' ).not( '.forminator-pagination-start' );

			$navigation.append( '<div class="forminator-break"></div>' );

			var self = this;

			steps.each( function() {

				var $step        = $( this ),
					$stepLabel   = $step.data( 'label' ),
					$stepNumb    = $step.data('step') - 1,
					$stepControl = 'forminator-custom-form-' + self.form_id + '--page-' + $stepNumb,
					$stepId      = $stepControl + '-label'
				;

				var $stepMarkup = '<button role="tab" id="' + $stepId + '" class="forminator-step forminator-step-' + $stepNumb + '" aria-selected="false" aria-controls="' + $stepControl + '" data-nav="' + $stepNumb + '">' +
					'<span class="forminator-step-label">' + $stepLabel + '</span>' +
					'<span class="forminator-step-dot" aria-hidden="true"></span>' +
				'</button>';

				var $stepBreak = '<div class="forminator-break" aria-hidden="true"></div>';

				$navigation.append( $stepMarkup + $stepBreak );

			});

			finalSteps.each(function () {
				var $step   = $(this),
					label   = $step.data('label'),
					numb    = steps.length,
					control = 'forminator-custom-form-' + self.form_id + '--page-' + numb,
					stepid  = control + '-label'
				;

				var $stepMarkup = '<button role="tab" id="' + stepid + '" class="forminator-step forminator-step-' + numb + '" data-nav="' + numb + '" aria-selected="false" aria-controls="' + control + '">' +
					'<span class="forminator-step-label">' + label + '</span>' +
					'<span class="forminator-step-dot" aria-hidden="true"></span>' +
				'</button>';

				var $stepBreak = '<div class="forminator-break" aria-hidden="true"></div>';

				$navigation.append( $stepMarkup + $stepBreak );
			});
		},

		/**
		 * Handle step click
		 *
		 * @param step
		 */
		handle_step: function( step ) {
			if ( this.settings.inline_validation ) {
				for ( var i = 0; i < step; i++ ) {
					if ( this.step <= i ) {
						if ( ! this.is_step_inputs_valid( i ) ) {
							this.go_to( i, true );
							return;
						}
					}
				}
			}
			this.go_to( step, true );
			this.update_buttons();
		},

		handle_click: function (type) {
			var self = this;
			if (type === "prev" && this.step !== 0) {
				this.go_to(this.step - 1, true);
				this.update_buttons();
			} else if (type === "next") {
				//do validation before next if inline validation enabled
				if (this.settings.inline_validation) {
					if ( ! this.is_step_inputs_valid( this.step ) ) {
						return;
					}
				}

				if(typeof this.$el.data().forminatorFrontPayment !== "undefined") {
					var payment = this.$el.data().forminatorFrontPayment,
						page = this.$el.find('[data-step=' + this.step + ']'),
						hasStripe = page.find(".forminator-stripe-element").not(".forminator-hidden .forminator-stripe-element")
					;


					// Check if Stripe exists on current step
					if (hasStripe.length > 0) {
						payment._stripe.createToken(payment._cardElement).then(function (result) {
							if (result.error) {
								payment.showCardError(result.error.message, true);
							} else {
								payment.hideCardError();
								self.go_to(self.step + 1, true);
								self.update_buttons();
							}
						});
					} else {
						this.go_to(this.step + 1, true);
						this.update_buttons();
					}
				} else {
					this.go_to(this.step + 1, true);
					this.update_buttons();
				}
			}

			// re-init textarea floating labels.
			var form = $( this.$el );
			var textarea = form.find( '.forminator-textarea' );
			var isMaterial = form.hasClass( 'forminator-design--material' );

			if ( isMaterial ) {
				if ( textarea.length ) {
					textarea.each( function() {
						FUI.textareaMaterial( this );
					});
				}
			}
		},

		/**
		 * Check current inputs on step is in valid state
		 */
		is_step_inputs_valid: function ( step ) {
			var valid = true,
				errors = 0,
				validator = this.$el.data('validator'),
				page = this.$el.find('[data-step=' + step + ']');

			//inline validation disabled
			if (typeof validator === 'undefined') {
				return true;
			}

			//get fields on current page
			page.find("input, select, textarea")
				.not(":submit, :reset, :image, :disabled")
				.not(':hidden:not(.forminator-wp-editor-required, .forminator-input-file-required, input[name$="_data"])')
				.not('[gramm="true"]')
				.each(function (key, element) {
					valid = validator.element(element);

					if (!valid) {
						if (errors === 0) {
							// focus on first error
							element.focus();
						}
						errors++;
					}
				});

			return errors === 0;
		},

		/**
		 * Get page on the input
		 *
		 * @since 1.0.3
		 *
		 * @param input
		 * @returns {number|*}
		 */
		get_page_of_input: function(input) {
			var step_page = this.step;
			var page = $(input).closest('.forminator-pagination');
			if (page.length > 0) {
				var step = $(page).data('step');
				if (typeof step !== 'undefined') {
					step_page = +step;
				}
			}

			return step_page;
		},

		update_buttons: function () {
			var hasDraft = this.$el.hasClass( 'draft-enabled' );

			if (this.step === 0) {
				if ( ! hasDraft ) {
					this.$el.find('.forminator-button-back').closest( '.forminator-pagination-footer' ).css({
						'justify-content': 'flex-end'
					});
				}

				this.$el.find('.forminator-button-back').addClass( 'forminator-hidden' );
				this.$el.find('.forminator-button-next').removeClass('forminator-hidden');
			} else {
				if ( this.totalSteps > 1 ) {
					if ( ! hasDraft ) {
						this.$el.find('.forminator-button-back').closest( '.forminator-pagination-footer' ).css({
							'justify-content': 'space-between'
						});
					}

					this.$el.find('.forminator-button-back, .forminator-button-next').removeClass('forminator-hidden');
				}
			}

			if (this.step === this.totalSteps && ! this.finished ) {
				//keep pagination content on last step before submit
				this.step--;
				this.$el.submit();
			}

			var submitButtonClass = this.settings.submitButtonClass;
			if ( this.step === ( this.totalSteps - 1 ) && ! this.finished ) {

				var submit_button_text = this.$el.find('.forminator-pagination-submit').html(),
					loadingText = this.$el.find('.forminator-pagination-submit').data('loading'),
					last_button_txt = ( this.custom_label[ 'pagination-labels' ] === 'custom'
						&& this.custom_label['last-previous'] !== '' ) ? this.custom_label['last-previous'] : this.prev_button;

				if ( this.$el.hasClass('forminator-design--material') ) {

					this.$el.find('.forminator-button-back .forminator-button--text').html( last_button_txt );
					this.$el.find('.forminator-button-next')
						.removeClass('forminator-button-next')
						.attr('id', 'forminator-submit')
						.addClass('forminator-button-submit ' + submitButtonClass )
						.find('.forminator-button--text')
						.html('')
						.html(submit_button_text).data('loading', loadingText);
				} else {
					this.$el.find('.forminator-button-back').html( last_button_txt );
					this.$el.find( '.forminator-button-next' )
						.removeClass( 'forminator-button-next' )
						.attr( 'id', 'forminator-submit' )
						.addClass( 'forminator-button-submit ' + submitButtonClass )
						.html( submit_button_text ).data('loading', loadingText);
				}

				var submitButton = this.$el.find( '.forminator-button-submit' );

				if ( ! submit_button_text ) {
					submitButton.addClass('forminator-hidden');
					if ( this.$el.find( '.forminator-submit-rightaway').length ) {
						submitButton.html( window.ForminatorFront.quiz.view_results );
					}
				}

				if( this.custom_label['has-paypal'] === true ) {
					submitButton.addClass('forminator-hidden');
					this.$el.find('.forminator-payment')
						.attr('id', 'forminator-paypal-submit');

					if ( false === window.paypalHasCondition ) {
						this.$el.find( '.forminator-payment' ).removeClass( 'forminator-hidden' );
					}
				}

				if ( this.$el.find('.forminator-payment iframe').length > 0 ) {
					this.$el.find('.forminator-payment iframe').width('100%');
				}

			} else {
				this.element = this.$el.find('[data-step=' + this.step + ']').data('name');
				if ( this.custom_label[this.element] && this.custom_label['pagination-labels'] === 'custom'){
					this.prev_button_txt = this.custom_label[this.element]['prev-text'] !== '' ? this.custom_label[this.element]['prev-text'] : this.prev_button;
					this.next_button_txt = this.custom_label[this.element]['next-text'] !== '' ? this.custom_label[this.element]['next-text'] : this.next_button;
				}else{
					this.prev_button_txt = this.prev_button;
					this.next_button_txt = this.next_button;
				}
				if ( this.step === ( this.totalSteps - 1 ) && this.finished ) {
					this.next_button_txt = window.ForminatorFront.quiz.view_results;
				}
				if ( this.$el.hasClass('forminator-design--material') ) {
					this.$el.find( '#forminator-submit' )
						.removeAttr( 'id' )
						.removeClass( 'forminator-button-submit forminator-hidden ' + submitButtonClass )
						.addClass( 'forminator-button-next' );
					if( this.custom_label['has-paypal'] === true ) {
						this.$el.find( '#forminator-paypal-submit' ).removeAttr( 'id' ).addClass('forminator-hidden');
						this.$el.find( '.forminator-button-next' ).removeClass( 'forminator-button-submit forminator-hidden ' + submitButtonClass );
					}

					this.$el.find( '.forminator-button-back .forminator-button--text' ).html( this.prev_button_txt );
					this.$el.find( '.forminator-button-next .forminator-button--text' ).html( this.next_button_txt );

				} else {
					this.$el.find( '#forminator-submit' )
						.removeAttr( 'id' )
						.removeClass( 'forminator-button-submit forminator-hidden' )
						.addClass( 'forminator-button-next' );
					if( this.custom_label['has-paypal'] === true ) {
						this.$el.find( '#forminator-paypal-submit' ).removeAttr( 'id' ).addClass('forminator-hidden');
						this.$el.find('.forminator-button-next').removeClass( 'forminator-button-submit forminator-hidden' );
					}
					this.$el.find( '.forminator-button-back' ).html( this.prev_button_txt );
					this.$el.find( '.forminator-button-next' ).html( this.next_button_txt );

				}
				if ( this.step === this.totalSteps && this.finished ) {
					this.$el.find('.forminator-button-next, .forminator-button-back').addClass( 'forminator-hidden' );
				}
			}
			// Reset the conditions to check if submit/paypal buttons should be visible
			this.$el.trigger( 'forminator.front.condition.restart' );
		},

		go_to: function (step, scrollToTop) {
			this.step = step;

			if (step === this.totalSteps && ! this.finished ) return false;

			// Hide all parts
			this.$el.find('.forminator-pagination').css({
				'height': '0',
				'opacity': '0',
				'visibility': 'hidden',
				'overflow': 'hidden'
			}).attr( 'aria-hidden', 'true' ).attr( 'hidden', true );

			this.$el.find('.forminator-pagination .forminator-pagination--content').hide();

			// Show desired page
			this.$el.find('[data-step=' + step + ']').css({
				'height': 'auto',
				'opacity': '1',
				'visibility': 'visible'
			}).removeAttr( 'aria-hidden' ).removeAttr( 'hidden' );

			this.$el.find('[data-step=' + step + '] .forminator-pagination--content').show();

			//exec responsive captcha
			var forminatorFront = this.$el.data('forminatorFront');
			if (typeof forminatorFront !== 'undefined') {
				forminatorFront.responsive_captcha();
			}

			this.update_navigation();

			if (scrollToTop) {
				this.scroll_to_top_form();
			}
		},

		update_navigation: function () {

			// Update navigation
			this.$el.find( '.forminator-current' ).attr( 'aria-selected', 'false' );
			this.$el.find( '.forminator-current' ).removeClass('forminator-current' );
			this.$el.find( '.forminator-step-' + this.step ).attr( 'aria-selected', 'true' );
			this.$el.find( '.forminator-step-' + this.step ).addClass( 'forminator-current' );

			this.$el.find( '.forminator-pagination:not(:hidden)' ).find( '.forminator-answer input' ).first().trigger( 'change' );

			this.calculate_bar_percentage();
		},

		/**
		 * Reset vertical screen position between sections
		 * https://app.asana.com/0/385581670491499/784073712068017/f
		 * Support Hustle Modal
		 */
		scroll_to_top_form: function () {
			var self            = this;
			var $element        = this.$el;
			// find first input row
			var first_input_row = this.$el.find('.forminator-row').not(':hidden').first();
			if (first_input_row.length) {
				$element = first_input_row;
			}

			if ($element.length) {
				var parent_selector = 'html,body';

				// check inside sui modal
				if (this.$el.closest('.sui-dialog').length > 0) {
					parent_selector = '.sui-dialog';
				}

				// check inside hustle modal (prioritize)
				if (this.$el.closest('.wph-modal').length > 0) {
					parent_selector = '.wph-modal';
				}

				$element.focus();

				var scrollTop = ($element.offset().top - ($(window).height() - $element.outerHeight(true)) / 2);
				if ( this.quiz ) {
					scrollTop = $element.offset().top;
					if ( $('#wpadminbar').length ) {
						scrollTop -= 35;
					}
				}

				$(parent_selector).animate({scrollTop: scrollTop}, 500, function () {
					if (!$element.attr("tabindex")) {
						$element.attr("tabindex", -1);
					}
				});
			}

		},

		resetRichTextEditorHeight: function () {
			if ( typeof tinyMCE !== 'undefined' ) {
				var form = this.$el,
					textarea = form.find( '.forminator-textarea' );

				textarea.each( function() {
					var tmceId = $( this ).attr( 'id' );

					if ( 0 !== form.find( '#'+ tmceId + '_ifr' ).length && form.find( '#'+ tmceId + '_ifr' ).is( ':visible' ) ) {
						form.find( '#' + tmceId + '_ifr' ).height( $( this ).height() );
					}
				});
			}
		},
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontPagination(this, options));
			}
		});
	};

})(jQuery, window, document);

// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "forminatorFrontPayPal",
		defaults   = {
			type: 'paypal',
			paymentEl: null,
			paymentRequireSsl: false,
			generalMessages: {},
		};

	// The actual plugin constructor
	function ForminatorFrontPayPal(element, options) {
		this.element = element;
		this.$el     = $(this.element);
		this.forminator_selector = '#' + this.$el.attr('id') + '[data-forminator-render="' + this.$el.data('forminator-render') + '"]';

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings              = $.extend({}, defaults, options);
		this._defaults             = defaults;
		this._name                 = pluginName;
		this.paypalData            = null;
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFrontPayPal.prototype, {
		init: function () {
			if (!this.settings.paymentEl) {
				return;
			}

			this.paypalData = this.settings.paymentEl;

			this.render_paypal_button( this.element );
		},

		is_data_valid: function() {
			var paypalData = this.configurePayPal(),
				requireSsl = this.settings.paymentRequireSsl
			;

			if ( paypalData.amount <= 0 ) {
				return false;
			}

			if ( requireSsl && 'https:' !== location.protocol ) {
				return false;
			}

			return true;
		},

		is_form_valid: function() {
			var validate = this.$el.validate(); // Get validate instance
			var isValid = validate.checkForm(); // Valid?
			validate.submitted = {}; // Reset immediate form field checking mode

			return isValid;
		},

		render_paypal_button: function ( form ) {
			var $form = $( form ),
				self = this,
				paypalData = this.configurePayPal(),
				$target_message = $form.find('.forminator-response-message'),
				paypalActions,
				error_msg = ForminatorFront.cform.gateway.error,
				requireSsl = this.settings.paymentRequireSsl,
				generalMessage = this.settings.generalMessages,
				style_data = {
					shape: paypalData.shape,
					color: paypalData.color,
					label: paypalData.label,
					layout: paypalData.layout,
					height: parseInt( paypalData.height ),
				};

			if( paypalData.layout !== 'vertical' ) {
				style_data.tagline =  paypalData.tagline;
			}

			paypal.Buttons({
				onInit: function(data, actions) {
					actions.disable();

					if ( paypalData.amount_type === 'variable' && paypalData.variable !== '' ) {
						paypalData.amount = self.get_field_calculation( paypalData.variable );
					}

					// Listen for form field changes
					$form.find('input, select, textarea').on( 'change', function() {
						if ( self.is_data_valid() && self.is_form_valid() ) {
							actions.enable();
						} else {
                            actions.disable();
                        }
					});

                    // Check if form has error to disable actions
                    $form.on( 'validation:error', function() {
                        actions.disable();
                    });

					// Check if the form is valid on init
					if ( self.is_data_valid() && self.is_form_valid() ) {
						actions.enable();
					}
				},

				env: paypalData.mode,
				style: style_data,
				onClick: function () {
					if( ! $form.valid() && paypalData.amount <= 0 ) {
						$target_message.removeClass('forminator-accessible').addClass('forminator-error').html('').removeAttr( 'aria-hidden' );
						$target_message.html('<label class="forminator-label--error"><span>' + generalMessage.payment_require_amount_error + '</span></label>');
						self.focus_to_element($target_message);
					} else if ( requireSsl && 'https:' !== location.protocol ) {
						$target_message.removeClass('forminator-accessible').addClass('forminator-error').html('').removeAttr( 'aria-hidden' );
						$target_message.html('<label class="forminator-label--error"><span>' + generalMessage.payment_require_ssl_error + '</span></label>');
						self.focus_to_element($target_message);
					} else if ( ! $form.valid() ) {
						$target_message.removeClass('forminator-accessible').addClass('forminator-error').html('').removeAttr( 'aria-hidden' );
						$target_message.html('<label class="forminator-label--error"><span>' + generalMessage.form_has_error + '</span></label>');
						self.focus_to_element($target_message);
                    }

					if ( paypalData.amount_type === 'variable' && paypalData.variable !== '' ) {
						paypalData.amount = self.get_field_calculation( paypalData.variable );
					}
				},
				createOrder: function(data, actions) {
					$form.addClass('forminator-partial-disabled');

					var nonce = $form.find('input[name="forminator_nonce"]').val(),
						form_id = self.getPayPalData('form_id'),
						request_data = self.paypal_request_data()
					;
					return fetch( ForminatorFront.ajaxUrl + '?action=forminator_pp_create_order', {
						method: 'POST',
						mode: "same-origin",
						credentials: "same-origin",
						headers: {
							'content-type': 'application/json'
						},
						body: JSON.stringify({
							nonce: nonce,
							form_id: form_id,
							mode: self.getPayPalData('mode'),
							form_data: request_data,
							form_fields: $form.serialize()
						})
					}).then(function(res) {
						return res.json();
					}).then(function(response) {
						if ( response.success !== true ) {
							error_msg = response.data;

							return false;
						}

						var orderId = response.data.order_id;
						$form.find('.forminator-paypal-input').val( orderId );

						return orderId;
					});
				},
				onApprove: function(data, actions) {
					if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
						// Disable form fields
						$form.addClass('forminator-fields-disabled');

						$target_message.html('<p>' + self.settings.loader_label + '</p>');

						$target_message.removeAttr("aria-hidden")
							.prop("tabindex", "-1")
							.removeClass('forminator-success forminator-error')
							.addClass('forminator-loading forminator-show');

						self.focus_to_element($target_message);
					}

					$form.trigger('submit');
				},

				onCancel: function (data, actions) {
					if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
						// Enable form fields
						$form.removeClass('forminator-fields-disabled forminator-partial-disabled');

						$target_message.removeClass('forminator-loading');
					}

					return actions.redirect();
				},
				onError: function () {
					if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
						// Enable form fields
						$form.removeClass('forminator-fields-disabled forminator-partial-disabled');

						$target_message.removeClass('forminator-loading');
					}

					$target_message.removeClass('forminator-accessible').addClass('forminator-error').html('').removeAttr( 'aria-hidden' );
					$target_message.html('<label class="forminator-label--error"><span>' + error_msg + '</span></label>');
					self.focus_to_element($target_message);
				},
			}).render( $form.find( '.forminator-button-paypal' )[0] );
		},

		configurePayPal: function () {
			var self   = this,
				paypalConfig = {
					form_id: this.getPayPalData('form_id'),
					sandbox_id: this.getPayPalData('sandbox_id'),
					currency: this.getPayPalData('currency'),
					live_id: this.getPayPalData('live_id'),
					amount: 0
				};

			paypalConfig.color = this.getPayPalData('color') ? this.getPayPalData('color') : 'gold';
			paypalConfig.shape = this.getPayPalData('shape') ? this.getPayPalData('shape') : 'rect';
			paypalConfig.label = this.getPayPalData('label') ? this.getPayPalData('label') : 'checkout';
			paypalConfig.layout = this.getPayPalData('layout') ? this.getPayPalData('layout') : 'vertical';
			paypalConfig.tagline = this.getPayPalData('tagline') ? this.getPayPalData('tagline') : 'true';
			paypalConfig.redirect_url = this.getPayPalData('redirect_url') ? this.getPayPalData('redirect_url') : '';
			paypalConfig.mode = this.getPayPalData('mode');
			paypalConfig.locale = this.getPayPalData('locale') ? this.getPayPalData('locale') : 'en_US';
			paypalConfig.debug_mode = this.getPayPalData('debug_mode') ? this.getPayPalData('debug_mode') : 'disable';
			paypalConfig.amount_type = this.getPayPalData('amount_type') ? this.getPayPalData('amount_type') : 'fixed';
			paypalConfig.variable = this.getPayPalData('variable') ? this.getPayPalData('variable') : '';
			paypalConfig.height = this.getPayPalData('height') ? this.getPayPalData('height') : 55;
			paypalConfig.shipping_address = this.getPayPalData('shipping_address') ? this.getPayPalData('shipping_address') : 55;

			var	amountType = this.getPayPalData('amount_type');
			if (amountType === 'fixed') {
				paypalConfig.amount = this.getPayPalData('amount');
			} else if( amountType === 'variable' && paypalConfig.variable !== '' ) {
				paypalConfig.amount =  this.get_field_calculation( paypalConfig.variable );
			}


			return paypalConfig;
		},

		getPayPalData: function (key) {
			if (typeof this.paypalData[key] !== 'undefined') {
				return this.paypalData[key];
			}

			return null;
		},

		// taken from forminatorFrontCondition
		get_form_field: function ( element_id ) {
			//find element by suffix -field on id input (default behavior)
			var $element = this.$el.find('#' + element_id + '-field');
			if ( $element.length === 0 ) {
				//find element by its on name (for radio on singlevalue)
				$element = this.$el.find('input[name=' + element_id + ']');
				if ( $element.length === 0 ) {
					// for text area that have uniqid, so we check its name instead
					$element = this.$el.find('textarea[name=' + element_id + ']');
					if ( $element.length === 0 ) {
						//find element by its on name[] (for checkbox on multivalue)
						$element = this.$el.find('input[name="' + element_id + '[]"]');
						if ( $element.length === 0 ) {
							//find element by direct id (for name field mostly)
							//will work for all field with element_id-[somestring]
							$element = this.$el.find('#' + element_id);
							if ( $element.length === 0 ) {
								$element = this.$el.find('select[name=' + element_id + ']');
							}
						}
					}
				}
			}

			return $element;
		},

		get_field_calculation: function (element_id) {
			var $element    = this.get_form_field(element_id);
			var value       = 0;
			var calculation = 0;
			var checked     = null;

			if (this.field_is_radio($element)) {
				checked = $element.filter(":checked");
				if (checked.length) {
					calculation = checked.data('calculation');
					if (calculation !== undefined) {
						value = Number(calculation);
					}
				}
			} else if (this.field_is_checkbox($element)) {
				$element.each(function () {
					if ($(this).is(':checked')) {
						calculation = $(this).data('calculation');
						if (calculation !== undefined) {
							value += Number(calculation);
						}
					}
				});

			} else if (this.field_is_select($element)) {
				checked = $element.find("option").filter(':selected');
				if (checked.length) {
					calculation = checked.data('calculation');
					if (calculation !== undefined) {
						value = Number(calculation);
					}
				}
			} else {
				if ( $element.inputmask ) {
					var unmaskVal =	$element.inputmask('unmaskedvalue');
					value = unmaskVal.replace(/,/g, '.');
				} else {
					value = Number( $element.val() );
				}
			}

			return isNaN(value) ? 0 : value;
		},

		focus_to_element: function ($element) {
			// force show in case its hidden of fadeOut
			$element.show();
			$('html,body').animate({scrollTop: ($element.offset().top - ($(window).height() - $element.outerHeight(true)) / 2)}, 500, function () {
				if (!$element.attr("tabindex")) {
					$element.attr("tabindex", -1);
				}
				$element.focus();
			});
		},

		paypal_request_data: function () {
			var paypalData = this.configurePayPal(),
				shipping_address = this.getPayPalData('shipping_address'),
				billing_details = this.getPayPalData('billing-details'),
				billingArr = this.getBillingData(),
				paypal_data = {};
			paypal_data.purchase_units = [{
				amount: {
					currency_code: this.getPayPalData('currency'),
					value: paypalData.amount
				}
			}];
			if ( 'disable' === shipping_address ) {
				paypal_data.application_context = {
					shipping_preference: "NO_SHIPPING",
				};
			}
			if ( billing_details ) {
				paypal_data.payer = billingArr;
			}

			return paypal_data;
		},

		getBillingData: function () {
			// Get billing fields
			var billingName = this.getPayPalData( 'billing-name' ),
				billingEmail = this.getPayPalData( 'billing-email' ),
				billingAddress = this.getPayPalData( 'billing-address' );

			// Create billing object
			var billingObject = {}

			if ( billingName ) {
				billingObject.name = {};
				var nameField = this.get_field_value( billingName );

				// Check if Name field is multiple
				if ( ! nameField ) {
					var pfix  = this.get_field_value( billingName + '-prefix' ) || '',
						fName = this.get_field_value( billingName + '-first-name' ) || '',
						mname = this.get_field_value( billingName + '-middle-name' ) || '',
						lName = this.get_field_value( billingName + '-last-name' ) || '';

					nameField = pfix ? pfix + ' ' : '';
					nameField += fName;
					nameField += mname ? ' ' + mname : '';
				}

				// Check if Name field is empty in the end, if not assign to the object
				if ( nameField ) {
					billingObject.name.given_name = nameField;
					billingObject.name.surname = lName;
				}
			}

			// Map email field
			if( billingEmail ) {
				var billingEmailValue = this.get_field_value( billingEmail ) || '';
				if ( billingEmailValue ) {
					billingObject.email_address = billingEmailValue;
				}
			}
			if ( billingAddress ) {
				billingObject.address = {};
				//  Map address line 1 field
				var addressLine1 = this.get_field_value(billingAddress + '-street_address') || '';
				if ( addressLine1 ) {
					billingObject.address.address_line_1 = addressLine1;
				}

				//Map address line 2 field
				var addressLine2 = this.get_field_value(billingAddress + '-address_line') || '';
				if ( addressLine2 ) {
					billingObject.address.address_line_2 = addressLine2;
				}

				// Map address city field
				var addressCity = this.get_field_value(billingAddress + '-city') || '';
				if ( addressCity ) {
					billingObject.address.admin_area_2 = addressCity;
				}

				// Map address state field
				var addressState = this.get_field_value(billingAddress + '-state') || '';
				if ( addressState ) {
					billingObject.address.admin_area_1 = addressState;
				}

				// Map address country field
				var countryField = this.get_form_field(billingAddress + '-country') || '';
				if ( countryField ) {
					billingObject.address.country_code = countryField.find(':selected').data('country-code');
				}

				// Map address country field
				var addressZip = this.get_field_value(billingAddress + '-zip') || '';
				if ( addressZip ) {
					billingObject.address.postal_code = addressZip;
				}
			}

			return billingObject;
		},

		get_field_value: function ( element_id ) {
			var $element = this.get_form_field( element_id );
			var value    = '';
			var checked  = null;

			if ( this.field_is_radio( $element ) ) {
				checked = $element.filter(":checked");
				if ( checked.length ) {
					value = checked.val();
				}
			} else if ( this.field_is_checkbox( $element ) ) {
				$element.each(function () {
					if ( $( this ).is(':checked') ) {
						value = $( this ).val();
					}
				});

			} else if ( this.field_is_select( $element ) ) {
				value = $element.val();
			} else {
				value = $element.val()
			}

			return value;
		},

		field_is_radio: function ( $element ) {
			var is_radio = false;
			$element.each(function () {
				if ( $(this).attr('type') === 'radio' ) {
					is_radio = true;
					//break
					return false;
				}
			});

			return is_radio;
		},

		field_is_checkbox: function ( $element ) {
			var is_checkbox = false;
			$element.each(function () {
				if ( $( this ).attr('type') === 'checkbox' ) {
					is_checkbox = true;
					//break
					return false;
				}
			});

			return is_checkbox;
		},

		field_is_select: function ( $element ) {
			return $element.is('select');
		},

	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontPayPal(this, options));
			}
		});
	};

})(jQuery, window, document);


// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "forminatorFrontDatePicker",
		defaults = {};

	// The actual plugin constructor
	function ForminatorFrontDatePicker(element, options) {
		this.element = element;
		this.$el = $(this.element);

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFrontDatePicker.prototype, {
		init: function () {
			var self = this,
				dateFormat = this.$el.data('format'),
				restrictType = this.$el.data('restrict-type'),
				restrict = this.$el.data('restrict'),
				restrictedDays = this.$el.data('restrict'),
				minYear = this.$el.data('start-year'),
				maxYear = this.$el.data('end-year'),
				pastDates = this.$el.data('past-dates'),
				dateValue = this.$el.val(),
				startOfWeek = this.$el.data('start-of-week'),
				minDate = this.$el.data('start-date'),
				maxDate = this.$el.data('end-date'),
				startField = this.$el.data('start-field'),
				endField = this.$el.data('end-field'),
				startOffset = this.$el.data('start-offset'),
				endOffset = this.$el.data('end-offset'),
				disableDate = this.$el.data('disable-date'),
				disableRange = this.$el.data('disable-range');

			//possible restrict only one
			if (!isNaN(parseFloat(restrictedDays)) && isFinite(restrictedDays)) {
				restrictedDays = [restrictedDays.toString()];
			} else {
				restrictedDays = restrict.split(',');
			}
			disableDate = disableDate.split(',');
			disableRange = disableRange.split(',');

			if (!minYear) {
				minYear = "c-95";
			}
			if (!maxYear) {
				maxYear = "c+95";
			}
			var disabledWeekDays = function ( current_date ) {
				return self.restrict_date( restrictedDays, disableDate, disableRange, current_date );
			};

			var parent = this.$el.closest('.forminator-custom-form'),
				add_class = "forminator-calendar";

			if ( parent.hasClass('forminator-design--default') ) {
				add_class = "forminator-calendar--default";
			} else if ( parent.hasClass('forminator-design--material') ) {
				add_class = "forminator-calendar--material";
			} else if ( parent.hasClass('forminator-design--flat') ) {
				add_class = "forminator-calendar--flat";
			} else if ( parent.hasClass('forminator-design--bold') ) {
				add_class = "forminator-calendar--bold";
			}


			this.$el.datepicker({
				"beforeShow": function (input, inst) {
					// Remove all Hustle UI related classes
					( inst.dpDiv ).removeClass( function( index, css ) {
						return ( css.match ( /\bhustle-\S+/g ) || []).join( ' ' );
					});

					// Remove all Forminator UI related classes
					( inst.dpDiv ).removeClass( function( index, css ) {
						return ( css.match ( /\bforminator-\S+/g ) || []).join( ' ' );
					});
					( inst.dpDiv ).addClass( 'forminator-custom-form-' + parent.data( 'form-id' ) + ' ' + add_class );
					// Enable/disable past dates
					if ( 'disable' === pastDates ) {
						$(this).datepicker( 'option', 'minDate', dateValue );
					} else {
						$(this).datepicker( 'option', 'minDate', null );
					}
					if( minDate ) {
						var min_date = new Date( minDate.replace(/-/g, '\/').replace(/T.+/, '') );
						$(this).datepicker( 'option', 'minDate', min_date );
					}
					if( maxDate ) {
						var max_date = new Date( maxDate.replace(/-/g, '\/').replace(/T.+/, '') );
						$(this).datepicker( 'option', 'maxDate', max_date );
					}
					if( startField ) {
						var startDateVal = self.getLimitDate( startField, startOffset );
						if( 'undefined' !== typeof startDateVal ) {
							$(this).datepicker( 'option', 'minDate', startDateVal );
						}
					}

					if( endField ) {
						var endDateVal = self.getLimitDate( endField, endOffset );
						if( 'undefined' !== typeof endDateVal ) {
							$(this).datepicker( 'option', 'maxDate', endDateVal );
						}
					}
				},
				"beforeShowDay": disabledWeekDays,
				"monthNames": datepickerLang.monthNames,
				"monthNamesShort": datepickerLang.monthNamesShort,
				"dayNames": datepickerLang.dayNames,
				"dayNamesShort": datepickerLang.dayNamesShort,
				"dayNamesMin": datepickerLang.dayNamesMin,
				"changeMonth": true,
				"changeYear": true,
				"dateFormat": dateFormat,
				"yearRange": minYear + ":" + maxYear,
				"minDate": new Date(minYear, 0, 1),
				"maxDate": new Date(maxYear, 11, 31),
				"firstDay" : startOfWeek,
				"onClose": function () {
					//Called when the datepicker is closed, whether or not a date is selected
					$(this).valid();
				},
			});

			//Disables google translator for the datepicker - this prevented that when selecting the date the result is presented as follows: NaN/NaN/NaN
			$('.ui-datepicker').addClass('notranslate');
		},

		getLimitDate: function ( dependentField, offset ) {
			var fieldVal = $('input[name ="'+ dependentField + '"]').val();
			if( typeof fieldVal !== 'undefined' ) {
				var DateFormat = $('input[name ="'+ dependentField + '"]').data('format').replace(/y/g, 'yy'),
					sdata = offset.split('_'),
					newDate = moment( fieldVal, DateFormat.toUpperCase() );
				if( '-' === sdata[0] ) {
					newDate = newDate.subtract( sdata[1], sdata[2] );
				} else {
					newDate = newDate.add( sdata[1], sdata[2] );
				}
				var formatedDate = moment( newDate ).format( 'YYYY-MM-DD' ),
					dateVal = new Date( formatedDate );

				return dateVal;
			}
		},

		restrict_date: function ( restrictedDays, disableDate, disableRange, date ) {
			var hasRange = true,
				day = date.getDay(),
				date_string = jQuery.datepicker.formatDate('mm/dd/yy', date);

			if ( 0 !== disableRange[0].length ) {
				for ( var i = 0; i < disableRange.length; i++ ) {

					var disable_date_range = disableRange[i].split("-"),
						start_date = new Date( disable_date_range[0].trim() ),
						end_date = new Date( disable_date_range[1].trim() );
					if ( date >= start_date && date <= end_date ) {
						hasRange = false;
						break;
					}
				}
			}

			if ( -1 !== restrictedDays.indexOf( day.toString() ) ||
				-1 !== disableDate.indexOf( date_string ) ||
				false === hasRange
			) {
				return [false, "disabledDate"]
			} else {
				return [true, "enabledDate"]
			}
		},
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontDatePicker(this, options));
			}
		});
	};

})(jQuery, window, document);
// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "forminatorFrontValidate",
	    ownMethods = {},
		defaults   = {
			rules: {},
			messages: {}
		};

	// The actual plugin constructor
	function ForminatorFrontValidate(element, options) {
		this.element = element;
		this.$el     = $(this.element);

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings  = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name     = pluginName;
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend( ForminatorFrontValidate.prototype, {

		init: function () {
			$( '.forminator-select2' ).on('change', this.element, function (e, param1) {
				if ( 'forminator_emulate_trigger' !== param1 ) {
					$( this ).trigger('focusout');
				}
			});

			var self      = this;
			var submitted = false;
			var $form     = this.$el;

			$( this.element ).validate({

				// add support for hidden required fields (uploads, wp_editor) when required
				ignore: ":hidden:not(.do-validate)",

				errorPlacement: function (error, element) {
					$form.trigger('validation:error');
				},

				showErrors: function(errorMap, errorList) {

					if( submitted && errorList.length > 0 ) {

						$form.find( '.forminator-response-message' ).html( '<ul></ul>' );

						jQuery.each( errorList, function( key, error ) {
							$form.find( '.forminator-response-message ul' ).append( '<li>' + error.message + '</li>' );
						});

						$form.find( '.forminator-response-message' )
							.removeAttr( 'aria-hidden' )
							.prop( 'tabindex', '-1' )
							.addClass( 'forminator-accessible' )
							;
					}

					submitted = false;

					this.defaultShowErrors();

					$form.trigger('validation:showError', errorList);
				},

				invalidHandler: function(form, validator){
					submitted = true;
					$form.trigger('validation:invalid');
				},

				onfocusout: function ( element ) {

					//datepicker will be validated when its closed
					if ( $( element ).hasClass('hasDatepicker') === false ) {
						$( element ).valid();
					}
					$( element ).trigger('validation:focusout');
				},

				highlight: function (element, errorClass, message) {

					var holder      = $( element );
					var holderField = holder.closest( '.forminator-field' );
					var holderDate  = holder.closest( '.forminator-date-input' );
					var holderTime  = holder.closest( '.forminator-timepicker' );
					var holderError = '';
					var getColumn   = false;
					var getError    = false;
					var getDesc     = false;

					var errorMessage = this.errorMap[element.name];
					var errorMarkup  = '<span class="forminator-error-message" aria-hidden="true"></span>';

					if ( holderDate.length > 0 ) {

						getColumn = holderDate.parent();
						getError  = getColumn.find( '.forminator-error-message[data-error-field="' + holder.data( 'field' ) + '"]' );
						getDesc   = getColumn.find( '.forminator-description' );

						errorMarkup = '<span class="forminator-error-message" data-error-field="' + holder.data( 'field' ) + '" aria-hidden="true"></span>';

						if ( 0 === getError.length ) {

							if ( 'day' === holder.data( 'field' ) ) {

								if ( getColumn.find( '.forminator-error-message[data-error-field="year"]' ).length ) {

									$( errorMarkup ).insertBefore( getColumn.find( '.forminator-error-message[data-error-field="year"]' ) );

								} else {

									if ( 0 === getDesc.length ) {
										getColumn.append( errorMarkup );
									} else {
										$( errorMarkup ).insertBefore( getDesc );
									}
								}

								if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

									holderField.append(
										'<span class="forminator-error-message" aria-hidden="true"></span>'
									);
								}
							}

							if ( 'month' === holder.data( 'field' ) ) {

								if ( getColumn.find( '.forminator-error-message[data-error-field="day"]' ).length ) {

									$( errorMarkup ).insertBefore(
										getColumn.find( '.forminator-error-message[data-error-field="day"]' )
									);

								} else {

									if ( 0 === getDesc.length ) {
										getColumn.append( errorMarkup );
									} else {
										$( errorMarkup ).insertBefore( getDesc );
									}
								}

								if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

									holderField.append(
										'<span class="forminator-error-message" aria-hidden="true"></span>'
									);
								}
							}

							if ( 'year' === holder.data( 'field' ) ) {

								if ( 0 === getDesc.length ) {
									getColumn.append( errorMarkup );
								} else {
									$( errorMarkup ).insertBefore( getDesc );
								}

								if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

									holderField.append(
										'<span class="forminator-error-message" aria-hidden="true"></span>'
									);
								}
							}
						}

						holderError = getColumn.find( '.forminator-error-message[data-error-field="' + holder.data( 'field' ) + '"]' );

						// Insert error message
						holderError.html( errorMessage );
						holderField.find( '.forminator-error-message' ).html( errorMessage );

					} else if ( holderTime.length > 0 ) {

						getColumn = holderTime.parent();
						getError  = getColumn.find( '.forminator-error-message[data-error-field="' + holder.data( 'field' ) + '"]' );
						getDesc   = getColumn.find( '.forminator-description' );

						errorMarkup = '<span class="forminator-error-message" data-error-field="' + holder.data( 'field' ) + '" aria-hidden="true"></span>';

						if ( 0 === getError.length ) {

							if ( 'hours' === holder.data( 'field' ) ) {

								if ( getColumn.find( '.forminator-error-message[data-error-field="minutes"]' ).length ) {

									$( errorMarkup ).insertBefore(
										getColumn.find( '.forminator-error-message[data-error-field="minutes"]' )
									);
								} else {

									if ( 0 === getDesc.length ) {
										getColumn.append( errorMarkup );
									} else {
										$( errorMarkup ).insertBefore( getDesc );
									}
								}

								if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

									holderField.append(
										'<span class="forminator-error-message" aria-hidden="true"></span>'
									);
								}
							}

							if ( 'minutes' === holder.data( 'field' ) ) {

								if ( 0 === getDesc.length ) {
									getColumn.append( errorMarkup );
								} else {
									$( errorMarkup ).insertBefore( getDesc );
								}

								if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

									holderField.append(
										'<span class="forminator-error-message" aria-hidden="true"></span>'
									);
								}
							}
						}

						holderError = getColumn.find( '.forminator-error-message[data-error-field="' + holder.data( 'field' ) + '"]' );

						// Insert error message
						holderError.html( errorMessage );
						holderField.find( '.forminator-error-message' ).html( errorMessage );

					} else {

						var getError = holderField.find( '.forminator-error-message' );
						var getDesc  = holderField.find( '.forminator-description' );

						if ( 0 === getError.length ) {

							if ( 0 === getDesc.length ) {
								holderField.append( errorMarkup );
							} else {
								$( errorMarkup ).insertBefore( getDesc );
							}
						}

						holderError = holderField.find( '.forminator-error-message' );

						// Insert error message
						holderError.html( errorMessage );

					}

					// Field invalid status for screen readers
					holder.attr( 'aria-invalid', 'true' );

					// Field error status
					holderField.addClass( 'forminator-has_error' );
					holder.trigger('validation:highlight');

				},

				unhighlight: function (element, errorClass, validClass) {

					var holder      = $( element );
					var holderField = holder.closest( '.forminator-field' );
					var holderTime  = holder.closest( '.forminator-timepicker' );
					var holderDate  = holder.closest( '.forminator-date-input' );
					var holderError = '';

					if ( holderDate.length > 0 ) {
						holderError = holderDate.parent().find( '.forminator-error-message[data-error-field="' + holder.data( 'field' ) + '"]' );
					} else if ( holderTime.length > 0 ) {
						holderError = holderTime.parent().find( '.forminator-error-message[data-error-field="' + holder.data( 'field' ) + '"]' );
					} else {
						holderError = holderField.find( '.forminator-error-message' );
					}

						// Remove invalid attribute for screen readers
					holder.removeAttr( 'aria-invalid' );

					// Remove error message
					holderError.remove();

					// Remove error class
					holderField.removeClass( 'forminator-has_error' );
					holder.trigger('validation:unhighlight');

				},

				rules: self.settings.rules,

				messages: self.settings.messages

			});

			$( this.element ).on('forminator.validate.signature', function () {
				//validator.element( $( this ).find( "input[id$='_data']" ) );
				var validator = $( this ).validate();
				validator.form();
			});
		}
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		// We need to restore our custom validation methods in case they were
		// lost or overwritten by another instantiation of the jquery.Validate plugin.
		$.each( ownMethods, function( key, method ) {
			if ( undefined === $.validator.methods[ key ] ) {
				$.validator.addMethod( key, method );
			} else if ( key === 'number' ) {
				$.validator.methods.number = ownMethods.number;
			}
		});
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontValidate(this, options));
			}
		});
	};
	$.validator.addMethod("validurl", function (value, element) {
		var url = $.validator.methods.url.bind(this);
		return url(value, element) || url('http://' + value, element);
	});
	$.validator.addMethod("forminatorPhoneNational", function (value, element) {
		// Uses intlTelInput to check if the number is valid.
		return this.optional(element) || $(element).intlTelInput('isValidNumber');
	});
	$.validator.addMethod("forminatorPhoneInternational", function (value, element) {
		// Uses intlTelInput to check if the number is valid.
		return this.optional(element) || $(element).intlTelInput('isValidNumber');
	});
	$.validator.addMethod("dateformat", function (value, element, param) {
		// dateITA method from jQuery Validator additional. Date method is deprecated and doesn't work for all formats
		var check = false,
			re    = 'yy-mm-dd' === param ||
					'yy/mm/dd' === param ||
					'yy.mm.dd' === param
				? /^\d{4}-\d{1,2}-\d{1,2}$/ : /^\d{1,2}-\d{1,2}-\d{4}$/,
			adata, gg, mm, aaaa, xdata;
		value = value.replace(/[ /.]/g, '-');
		if (re.test(value)) {
			if ('dd/mm/yy' === param || 'dd-mm-yy' === param || 'dd.mm.yy' === param) {
				adata = value.split("-");
				gg    = parseInt(adata[0], 10);
				mm    = parseInt(adata[1], 10);
				aaaa  = parseInt(adata[2], 10);
			} else if ('mm/dd/yy' === param || 'mm.dd.yy' === param || 'mm-dd-yy' === param) {
				adata = value.split("-");
				mm    = parseInt(adata[0], 10);
				gg    = parseInt(adata[1], 10);
				aaaa  = parseInt(adata[2], 10);
			} else {
				adata = value.split("-");
				aaaa  = parseInt(adata[0], 10);
				mm    = parseInt(adata[1], 10);
				gg    = parseInt(adata[2], 10);
			}
			xdata = new Date(Date.UTC(aaaa, mm - 1, gg, 12, 0, 0, 0));
			if ((xdata.getUTCFullYear() === aaaa) && (xdata.getUTCMonth() === mm - 1) && (xdata.getUTCDate() === gg)) {
				check = true;
			} else {
				check = false;
			}
		} else {
			check = false;
		}
		return this.optional(element) || check;
	});
	$.validator.addMethod("maxwords", function (value, element, param) {
		return this.optional(element) || value.trim().split(/\s+/).length <= param;
	});
	$.validator.addMethod("trim", function( value, element, param ) {
		return true === this.optional( element ) || 0 !== value.trim().length;
	});
	$.validator.addMethod("emailWP", function (value, element, param) {
		if (this.optional(element)) {
			return true;
		}

		// Test for the minimum length the email can be
		if (value.trim().length < 6) {
			return false;
		}

		// Test for an @ character after the first position
		if (value.indexOf('@', 1) < 0) {
			return false;
		}

		// Split out the local and domain parts
		var parts = value.split('@', 2);

		// LOCAL PART
		// Test for invalid characters
		if (!parts[0].match(/^[a-zA-Z0-9!#$%&'*+\/=?^_`{|}~\.-]+$/)) {
			return false;
		}

		// DOMAIN PART
		// Test for sequences of periods
		if (parts[1].match(/\.{2,}/)) {
			return false;
		}

		var domain = parts[1];
		// Split the domain into subs
		var subs   = domain.split('.');
		if (subs.length < 2) {
			return false;
		}

		var subsLen = subs.length;
		for (var i = 0; i < subsLen; i++) {
			// Test for invalid characters
			if (!subs[i].match(/^[a-z0-9-]+$/i)) {
				return false;
			}
		}

		return true;
	});
	$.validator.addMethod("forminatorPasswordStrength", function (value, element, param) {
		var passwordStrength = value.trim();

		// Password is optional and is empty so don't check strength.
		if ( passwordStrength.length == 0 ) {
			return true;
		}

		//at least 8 characters
		if ( ! passwordStrength || passwordStrength.length < 8) {
			return false;
		}

		var symbolSize = 0, natLog, score;
		//at least one number
		if ( passwordStrength.match(/[0-9]/) ) {
			symbolSize += 10;
		}
		//at least one lowercase letter
		if ( passwordStrength.match(/[a-z]/) ) {
			symbolSize += 20;
		}
		//at least one uppercase letter
		if ( passwordStrength.match(/[A-Z]/) ) {
			symbolSize += 20;
		}
		if ( passwordStrength.match(/[^a-zA-Z0-9]/) ) {
			symbolSize += 30;
		}
		//at least one special character
		if ( passwordStrength.match(/[=!\-@.,_*#&?^`%$+\/{\[\]|}^?~]/) ) {
			symbolSize += 30;
		}

		natLog = Math.log( Math.pow(symbolSize, passwordStrength.length) );
		score = natLog / Math.LN2;

		return score >= 54;
	});

	$.validator.addMethod("extension", function (value, element, param) {
		var check = false;
		if (value.trim() !== '') {
			var extension = value.replace(/^.*\./, '');
			if (extension == value) {
				extension = 'notExt';
			} else {
				extension = extension.toLowerCase();
			}

			if (param.indexOf(extension) != -1) {
				check = true;
			}
		}

		return this.optional(element) || check;
	});

	// $.validator.methods.required = function(value, element, param) {
	// 	console.log("required", element);
	//
	// 	return someCondition && value != null;
	// }

	// override core jquertvalidation number, to use HTML5 spec
	$.validator.methods.number = function (value, element, param) {
		return this.optional(element) || /^[-+]?[0-9]+[.]?[0-9]*([eE][-+]?[0-9]+)?$/.test(value);
	};

	$.validator.addMethod('minNumber', function (value, el, param) {
		if ( 0 === value.length ) {
			return true;
		}
		var minVal = parseFloatFromString( value );
		return minVal >= param;
	});
	$.validator.addMethod('maxNumber', function (value, el, param) {
		if ( 0 === value.length ) {
			return true;
		}
		var maxVal = parseFloatFromString( value );
		return maxVal <= param;
	});

	function parseFloatFromString( value ) {
		value = String( value ).trim();

		var parsed = parseFloat( value );
		if ( String( parsed ) === value ) {
			return fixDecimals( parsed, 2 );
		}

		var split = value.split( /[^\dE-]+/ );

		if ( 1 === split.length ) {
			return fixDecimals(parseFloat(value), 2);
		}

		var decimal = split.pop();

		// reconstruct the number using dot as decimal separator
		return fixDecimals( parseFloat( split.join('') +  '.' + decimal ), 2 );
	}

	function fixDecimals( num, precision ) {
		return ( Math.floor( num * 100 ) / 100 ).toFixed( precision );
	}

	// Backup the recently added custom validation methods (they will be 
	// checked in the plugin wrapper later)
	ownMethods = $.validator.methods;

})(jQuery, window, document);
// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	window.paypalHasCondition = false;

	// Create the defaults once
	var pluginName = "forminatorFrontCondition",
		defaults = {
			fields: {},
			relations: {}
		};

	// The actual plugin constructor
	function ForminatorFrontCondition(element, options, calendar) {
		this.element = element;
		this.$el = $(this.element);

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;
		this.calendar = calendar[0];
		this.init();
	}
	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFrontCondition.prototype, {
		init: function () {
			var self = this,
				form = this.$el,
				$forminatorFields = this.$el.find( ".forminator-field input, .forminator-row input[type=hidden], .forminator-field select, .forminator-field textarea, .forminator-field-signature")
				;

			this.add_missing_relations();

			$forminatorFields.on( 'change input forminator.change', function (e) {
				var $element = $(this),
					element_id = $element.closest('.forminator-col').attr('id')
					;

				if (typeof element_id === 'undefined') {
                    /*
                     * data-multi attribute was added to Name field - multiple
                     * We had to use name attribute for Name multi-field because we cannot change
                     * the IDs of elements. Some functions rely on the ID text pattern already.
                     */
                    if ( $element.attr( 'data-multi' ) === '1' ) {
					   element_id = $element.attr( 'name' );
                    } else {
					   element_id = $element.attr( 'id' );
                    }
				}
				element_id = element_id.trim();
				//lookup condition of fields
				if (!self.has_relations(element_id) && !self.has_siblings(element_id)) return false;

				if( self.has_siblings(element_id) ) {
					self.trigger_fake_parent_date_field(element_id);
				}
				if(!self.has_relations(element_id) && self.has_siblings(element_id)){
					self.trigger_siblings(element_id);
					return false;
				}

				self.process_relations( element_id, $element, e );

				self.paypal_button_condition();

				self.maybe_clear_upload_container();
			});

            // Trigger change event to textarea that has tinyMCE editor
            // For non-ajax form load
            $( document ).on( 'tinymce-editor-init', function ( event, editor ) {
                editor.on( 'change', function( e ) {
                    form.find( '#' + $(this).attr( 'id' ) ).change();
                });
            });
            // For ajax form load
            if ( typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor ) {
                tinyMCE.activeEditor.on( 'change', function( e ) {
                    form.find( '#' + $(this).attr( 'id' ) ).change();
                });
            }

			this.$el.find('.forminator-button.forminator-button-back, .forminator-button.forminator-button-next').on("click", function () {
				form.find('.forminator-field input:not([type="file"]), .forminator-row input[type=hidden], .forminator-field select, .forminator-field textarea').trigger( 'forminator.change', 'forminator_emulate_trigger' );
			});
			// Simulate change
			this.$el.find('.forminator-field input, .forminator-row input[type=hidden], .forminator-field select, .forminator-field textarea').trigger( 'forminator.change', 'forminator_emulate_trigger' );
			this.init_events();
		},

		process_relations: function( element_id, $element, e ) {
			var self = this;
			// Check if the field has any relations
			var relations = self.get_relations( element_id );
			// Loop all relations the field have
			relations.forEach(function (relation) {
				var logic = self.get_field_logic(relation),
					action = logic.action,
					rule = logic.rule,
					conditions = logic.conditions, // Conditions rules
					matches = 0 // Number of matches
				;

				// If paypal has logic set paypalHasCondition to true
				if ( 0 === relation.indexOf( 'paypal' ) ) {
					if ( 0 !== logic.length ) {
						window.paypalHasCondition = true;
					}
				}

				conditions.forEach(function (condition) {
					// If rule is applicable save in matches
					if (self.is_applicable_rule(condition, action)) {
						matches++;
					}
				});

				if ((rule === "all" && matches === conditions.length) || (rule === "any" && matches > 0)) {
					//check if the given $element is an jQuery object
					if( $element instanceof jQuery ) {
						var pagination = $element.closest('.forminator-pagination');
					}
					if (relation === 'submit' && typeof pagination !== 'undefined') {
						self.toggle_field(relation, 'show', "valid");
					}
					self.toggle_field(relation, action, "valid");
					if (self.has_relations(relation)){
						if(action === 'hide'){
							self.hide_element(relation, e);
						}else{
							self.show_element(relation, e);
						}
					}
				} else {
					self.toggle_field(relation, action, "invalid");
					if (self.has_relations(relation)){
						if(action === 'show'){
							self.hide_element(relation, e);
						}else{
							self.show_element(relation, e);
						}
					}
				}
			});
		},

		/**
		 * Register related events
		 *
		 * @since 1.0.3
		 */
		init_events: function () {
			var self = this;
			this.$el.on('forminator.front.condition.restart', function (e) {
				self.on_restart(e);
			});
		},

		/**
		 * Restart conditions
		 *
		 * @since 1.0.3
		 *
		 * @param e
		 */
		on_restart: function (e) {
			// restart condition
			this.$el.find('.forminator-field input:not([type="file"]), .forminator-row input[type=hidden], .forminator-field select, .forminator-field textarea').trigger( 'change', 'forminator_emulate_trigger' );
		},

		/**
		 * Add missing relations based on fields.conditions
		 */
		add_missing_relations: function () {
			var self = this;
			var missedRelations = {};
			if (typeof this.settings.fields !== "undefined") {
				var conditionsFields = this.settings.fields;
				Object.keys(conditionsFields).forEach(function (key) {
					var conditions = conditionsFields[key]['conditions'];
					conditions.forEach(function (condition) {
						var relatedField = condition.field;
						if (!self.has_relations(relatedField)) {
							if (typeof missedRelations[relatedField] === 'undefined') {
								missedRelations[relatedField] = [];
							}
							missedRelations[relatedField].push(key);

						}
					});
				});
			}
			Object.keys(missedRelations).forEach(function (relatedField) {
				self.settings.relations[relatedField] = missedRelations[relatedField];
			});
		},

		get_field_logic: function (element_id) {
			if (typeof this.settings.fields[element_id] === "undefined") return [];
			return this.settings.fields[element_id];
		},

		has_relations: function (element_id) {
			return typeof this.settings.relations[element_id] !== "undefined";
		},

		get_relations: function (element_id) {
			if (!this.has_relations(element_id)) return [];

			return this.settings.relations[element_id];
		},

		get_field_value: function (element_id) {
            if ( '' === element_id ) {
                return '';
            }

			var $element = this.get_form_field(element_id),
				value = $element.val();

			//check the type of input
			if (this.field_is_radio($element)) {
				value = $element.filter(":checked").val();
			} else if (this.field_is_signature($element)) {
				value = $element.find( "input[id$='_data']" ).val();
			} else if (this.field_is_checkbox($element)) {
				value = [];
				$element.each(function () {
					if ($(this).is(':checked')) {
						value.push($(this).val().toLowerCase());
					}
				});

				// if value is empty, return it as null
                if ( 0 === value.length ) {
                    value = null;
                }
			} else if ( this.field_is_textarea_wpeditor( $element ) ) {
                if ( typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor ) {
                    value = tinyMCE.activeEditor.getContent();
                }
			} else if ( this.field_has_inputMask( $element ) ) {
				value = $element.inputmask( 'unmaskedvalue' );
			}
			if (!value) return "";

			return value;
		},

		get_date_field_value: function(element_id){
            if ( '' === element_id ) {
                return '';
            }

			var $element = this.get_form_field(element_id);
			//element may not be a real jQuery element for fake virtual parent date field
			var fake_field = true;
			if( $element instanceof jQuery ) {
				fake_field = false;
				//element may just be the wrapper div of child fields
				if( $element.hasClass('forminator-col') ) {
					fake_field = true;
				}
			}

			var value = "";

			if ( !fake_field && this.field_is_datepicker($element) ){
				value = $element.val();
				//check if formats are accepted
				switch ( $element.data('format') ) {
					case 'dd/mm/yy':
						value = $element.val().split("/").reverse().join("-");
						break;
					case 'dd.mm.yy':
						value = $element.val().split(".").reverse().join("-");
						break;
					case 'dd-mm-yy':
						value = $element.val().split("-").reverse().join("-");
						break;
             	}

            	var formattedDate = new Date();

				if ( '' !== value ) {
					formattedDate = new Date(value);
				}

				value = {'year':formattedDate.getFullYear(), 'month':formattedDate.getMonth(), 'date':formattedDate.getDate(), 'day':formattedDate.getDay() };

			} else {

				var parent 	 = ( fake_field === true )? element_id : $element.data('parent');
				var	year 	 = this.get_form_field_value(parent+'-year'),
					mnth 	 = this.get_form_field_value(parent+'-month'),
					day  	 = this.get_form_field_value(parent+'-day');

				if( year !== "" && mnth !== "" && day !== "" ){
					var formattedDate = new Date( year + '-' + mnth + '-' + day );
					value = {'year':formattedDate.getFullYear(), 'month':formattedDate.getMonth(), 'date':formattedDate.getDate(), 'day':formattedDate.getDay() };
				}

			}

			if (!value) return "";

			return value;

		},

		field_has_inputMask: function ( $element ) {
			var hasMask = false;

			$element.each(function () {
				if ( undefined !== $( this ).attr( 'data-inputmask' ) ) {
					hasMask = true;
					//break
					return false;
				}
			});

			return hasMask;
		},

		field_is_radio: function ($element) {
			var is_radio = false;
			$element.each(function () {
				if ($(this).attr('type') === 'radio') {
					is_radio = true;
					//break
					return false;
				}
			});

			return is_radio;
		},

		field_is_signature: function($element) {
			var is_signature = false;

			$element.each(function () {
				if ($(this).find('.forminator-field-signature').length > 0) {
					is_signature = true;
					//break
					return false;
				}
			});

			return is_signature;
		},

		field_is_datepicker: function ($element) {
			var is_date = false;
			$element.each(function () {
				if ($(this).hasClass('forminator-datepicker')) {
					is_date = true;
					//break
					return false;
				}
			});

			return is_date;
		},

		field_is_checkbox: function ($element) {
			var is_checkbox = false;
			$element.each(function () {
				if ($(this).attr('type') === 'checkbox') {
					is_checkbox = true;
					//break
					return false;
				}
			});

			return is_checkbox;
		},

		/* field_is_consent: function ( $element ) {
			var is_consent = false;

			$( 'input[name="' + $element + '"]' ).each(function () {
				if ( $element.indexOf( 'consent' ) >= 0 ) {
					is_consent = true;
					//break
					return false;
				}
			});

			return is_consent;
		}, */

		field_is_select: function ($element) {
			return $element.is('select');
		},

        field_is_textarea_wpeditor: function ($element) {
			var is_textarea_wpeditor = false;
			$element.each(function () {
				if ( $(this).parent( '.wp-editor-container' ).parent( 'div' ).hasClass( 'tmce-active' ) ) {
					is_textarea_wpeditor = true;
					//break
					return false;
				}
			});

			return is_textarea_wpeditor;
		},

        field_is_upload: function ($element) {
			var is_upload = false;

			if ( -1 !== $element.indexOf( 'upload' ) ) {
				is_upload = true;
			}

			return is_upload;
		},

		// used in forminatorFrontCalculate
		get_form_field: function (element_id) {
			//find element by suffix -field on id input (default behavior)
			var $element = this.$el.find('#' + element_id + '-field');
			if ($element.length === 0) {
				$element = this.$el.find('.' + element_id + '-payment');
				if ($element.length === 0) {
					//find element by its on name (for radio on singlevalue)
					$element = this.$el.find('input[name="' + element_id + '"]');
					if ($element.length === 0) {
						// for text area that have uniqid, so we check its name instead
						$element = this.$el.find('textarea[name="' + element_id + '"]');
						if ($element.length === 0) {
							//find element by its on name[] (for checkbox on multivalue)
							$element = this.$el.find('input[name="' + element_id + '[]"]');
							if ($element.length === 0) {
								//find element by select name
								$element = this.$el.find('select[name="' + element_id + '"]');
								if ($element.length === 0) {
									//find element by direct id (for name field mostly)
									//will work for all field with element_id-[somestring]
									$element = this.$el.find('#' + element_id);
								}
							}
						}
					}
				}
			}

			return $element;
		},

		// Extension of get_form_field to get value
		get_form_field_value: function (element_id) {
			//find element by suffix -field on id input (default behavior)
			var $form_id = this.$el.data( 'form-id' ),
				$uid 	 = this.$el.data( 'uid' ),
				$element = this.$el.find('#forminator-form-' + $form_id + '__field--' + element_id + '_' + $uid );
			if ($element.length === 0) {
				var $element = this.$el.find('#' + element_id + '-field' );
				if ($element.length === 0) {
					//find element by its on name (for radio on singlevalue)
					$element = this.$el.find('input[name="' + element_id + '"]');
					if ($element.length === 0) {
						// for text area that have uniqid, so we check its name instead
						$element = this.$el.find('textarea[name="' + element_id + '"]');
						if ($element.length === 0) {
							//find element by its on name[] (for checkbox on multivalue)
							$element = this.$el.find('input[name="' + element_id + '[]"]');
							if ($element.length === 0) {
								//find element by select name
								$element = this.$el.find('select[name="' + element_id + '"]');
								if ($element.length === 0) {
									//find element by direct id (for name field mostly)
									//will work for all field with element_id-[somestring]
									$element = this.$el.find('#' + element_id);
								}
							}
						}
					}
				}
			}

			return $element.val();
		},

		is_numeric: function (number) {
			return !isNaN(parseFloat(number)) && isFinite(number);
		},

		is_date_rule: function(operator){

			var dateRules  = ['day_is', 'day_is_not', 'month_is', 'month_is_not', 'is_before', 'is_after', 'is_before_n_or_more_days', 'is_before_less_than_n_days', 'is_after_n_or_more_days', 'is_after_less_than_n_days'];

			return dateRules.includes( operator );

		},

		has_siblings: function(element){
            if ( '' === element ) {
                return false;
            }

			element = this.get_form_field(element);
			if( element.data('parent') ) return true;
			return false;

		},

		trigger_fake_parent_date_field: function(element_id){
			var	element = this.get_form_field(element_id),
				parent  = element.data('parent');
				this.process_relations( parent, {}, {});
		},

		trigger_siblings: function(element_id){
			var self = this,
				element = self.get_form_field(element_id),
				parent = element.data('parent'),
				siblings = [];

			siblings 	= [parent+'-year', parent+'-month', parent+'-day'];

			$.each(siblings, function( index, sibling ) {
			  	if( element_id !== sibling && self.has_relations(sibling) ){
					self.get_form_field(sibling).trigger('change');
				}
			});

		},

		is_applicable_rule: function (condition, action) {
			if (typeof condition === "undefined") return false;

			if( this.is_date_rule( condition.operator ) ){
				var value1 = this.get_date_field_value(condition.field);
			}else{
				var value1 = this.get_field_value(condition.field);
			}

			var value2 = condition.value,
				operator = condition.operator
			;

			if (action === "show") {
				return this.is_matching(value1, value2, operator) && this.is_hidden(condition.field);
			} else {
				return this.is_matching(value1, value2, operator);
			}
		},

		is_hidden: function (element_id) {
			var $element_id = this.get_form_field(element_id),
				$column_field = $element_id.closest('.forminator-col'),
				$row_field = $column_field.closest('.forminator-row')
			;

			if ( $row_field.hasClass("forminator-hidden-option") ) {
				return true;
			}

			if( $row_field.hasClass("forminator-hidden") ) {
				return false;
			}

			return true;
		},

		is_matching: function (value1, value2, operator) {
			// Match values case
			var isArrayValue = Array.isArray(value1);

			// Match values case
			if (typeof value1 === 'string') {
				value1 = value1.toLowerCase();
			}

			if(typeof value2 === 'string'){
				value2 = value2.toLowerCase();

				if(operator === 'month_is' || operator === 'month_is_not'){
					var months = {
						'jan':0,
						'feb':1,
						'mar':2,
						'apr':3,
						'may':4,
						'jun':5,
						'jul':6,
						'aug':7,
						'sep':8,
						'oct':9,
						'nov':10,
						'dec':11
					};
					if($.inArray(value2, months)){
						value2 = months[ value2 ];
					}
				}
				if(operator === 'day_is' || operator === 'day_is_not'){
					var days = {
						'su':0,
						'mo':1,
						'tu':2,
						'we':3,
						'th':4,
						'fr':5,
						'sa':6
					};
					if($.inArray(value2, days)){
						value2 = days[ value2 ];
					}
				}
			}

			switch (operator) {
				case "is":
					if (!isArrayValue) {
						if ( this.is_numeric( value1 ) && this.is_numeric( value2 ) ) {
							return Number( value1 ) === Number( value2 );
						}

						return value1 === value2;
					} else {
						return $.inArray(value2, value1) > -1;
					}
				case "is_not":
					if (!isArrayValue) {
						return value1 !== value2;
					} else {
						return $.inArray(value2, value1) === -1;
					}
				case "is_great":
					// typecasting to integer, with return `NaN` when its literal chars, so `is_numeric` will fail
					value1 = +value1;
					value2 = +value2;
					return this.is_numeric(value1) && this.is_numeric(value2) ? value1 > value2 : false;
				case "is_less":
					value1 = +value1;
					value2 = +value2;
					return this.is_numeric(value1) && this.is_numeric(value2) ? value1 < value2 : false;
				case "contains":
					return this.contains(value1, value2);
				case "starts":
					return value1.startsWith(value2);
				case "ends":
					return value1.endsWith(value2);
				case "month_is":
					return value1.month === value2;
				case "month_is_not":
					return value1.month !== value2;
				case "day_is":
					return value1.day === value2;
				case "day_is_not":
					return value1.day !== value2;
				case "is_before":
					return this.date_is_smaller( value1, value2 );
				case "is_after":
					return this.date_is_grater( value1, value2 );
				case "is_before_n_or_more_days":
					return this.date_is_n_days_before_current_date( value1, value2 );
				case "is_before_less_than_n_days":
					return this.date_is_less_than_n_days_before_current_date( value1, value2 );
				case "is_after_n_or_more_days":
					return this.date_is_n_days_after_current_date( value1, value2 );
				case "is_after_less_than_n_days":
					return this.date_is_less_than_n_days_after_current_date( value1, value2 );
			}

			// Return false if above are not valid
			return false;
		},

		contains: function (field_value, value) {
			return field_value.toLowerCase().indexOf(value) >= 0;
		},

		date_is_grater: function( date1, date2 ) {
			return forminatorDateUtil.compare( date1, date2 ) === 1;
		},

		date_is_smaller: function( date1, date2 ) {
			return forminatorDateUtil.compare( date1, date2 ) === -1;
		},

		date_is_equal: function( date1, date2 ) {
			return forminatorDateUtil.compare( date1, date2 ) === 0;
		},

		date_is_n_days_before_current_date: function( date1, n ) {
			n = parseInt( n );
			var current_date = this.get_current_date();
			var diff = forminatorDateUtil.diffInDays( date1, current_date );
			if( isNaN( diff ) ) {
				return false;
			}
			if( n === 0 ) {
				return ( diff === n );
			} else {
				return ( diff >= n );
			}
		},

		date_is_less_than_n_days_before_current_date: function( date1, n ) {
			n = parseInt( n );
			var current_date = this.get_current_date();
			var diff = forminatorDateUtil.diffInDays( date1, current_date );
			if( isNaN( diff ) ) {
				return false;
			}

			return ( diff < n && diff > 0 );
		},

		date_is_n_days_after_current_date: function( date1, n ) {
			n = parseInt( n );
			var current_date = this.get_current_date();
			var diff = forminatorDateUtil.diffInDays( current_date, date1 );
			if( isNaN( diff ) ) {
				return false;
			}
			if( n === 0 ) {
				return ( diff === n );
			} else {
				return ( diff >= n );
			}
		},

		date_is_less_than_n_days_after_current_date: function( date1, n ) {
			n = parseInt( n );
			var current_date = this.get_current_date();
			var diff = forminatorDateUtil.diffInDays( current_date, date1 );
			if( isNaN( diff ) ) {
				return false;
			}

			return ( diff < n && diff > 0 );
		},

		get_current_date: function() {
			return new Date();
		},

		toggle_field: function (element_id, action, type) {
			var $element_id = this.get_form_field(element_id),
				$column_field = $element_id.closest('.forminator-col'),
				$hidden_upload = $column_field.find('.forminator-input-file-required'),
				$hidden_signature = $column_field.find('[id ^=ctlSignature][id $=_data]'),
				$hidden_wp_editor = $column_field.find('.forminator-wp-editor-required'),
				$row_field = $column_field.closest('.forminator-row'),
				$pagination_next_field = this.$el.find('.forminator-pagination-footer').find('.forminator-button-next'),
				submit_selector = 'submit' === element_id ? '.forminator-button-submit' : '#forminator-paypal-submit',
				$pagination_field = this.$el.find( submit_selector )
				;

			// Handle show action
			if (action === "show") {
				if (type === "valid") {
					$row_field.removeClass('forminator-hidden');
					$column_field.removeClass('forminator-hidden');
					$pagination_next_field.removeClass('forminator-hidden');
					if ($hidden_upload.length > 0) {
						$hidden_upload.addClass('do-validate');
					}
					if ($hidden_wp_editor.length > 0) {
						$hidden_wp_editor.addClass('do-validate');
					}
					if ($hidden_signature.length > 0) {
						$hidden_signature.addClass('do-validate');
					}
					if ( 'submit' === element_id ) {
						$pagination_field.removeClass('forminator-hidden');
					}
					if ( 0 === element_id.indexOf( 'paypal' ) ) {
						$pagination_field.removeClass('forminator-hidden');
					}
				} else {
					$column_field.addClass('forminator-hidden');
					if ( 'submit' === element_id ) {
						$pagination_field.addClass('forminator-hidden');
					}
					if ( 0 === element_id.indexOf( 'paypal' ) ) {
						$pagination_field.addClass('forminator-hidden');
					}
					if ($hidden_upload.length > 0) {
						$hidden_upload.removeClass('do-validate');
					}
					if ($hidden_wp_editor.length > 0) {
						$hidden_wp_editor.removeClass('do-validate');
					}
					if ($hidden_signature.length > 0) {
						$hidden_signature.removeClass('do-validate');
					}
					if ($row_field.find('> .forminator-col:not(.forminator-hidden)').length === 0) {
						$row_field.addClass('forminator-hidden');
					}
				}
			}

			// Handle hide action
			if (action === "hide") {
				if (type === "valid") {
					$column_field.addClass('forminator-hidden');
					$pagination_field.addClass('forminator-hidden');
					if ($hidden_upload.length > 0) {
						$hidden_upload.removeClass('do-validate');
					}
					if ($hidden_wp_editor.length > 0) {
						$hidden_wp_editor.removeClass('do-validate');
					}
					if ($hidden_signature.length > 0) {
						$hidden_signature.removeClass('do-validate');
					}
					if ($row_field.find('> .forminator-col:not(.forminator-hidden)').length === 0) {
						$row_field.addClass('forminator-hidden');
					}
				} else {
					$row_field.removeClass('forminator-hidden');
					$column_field.removeClass('forminator-hidden');
					$pagination_field.removeClass('forminator-hidden');
					if ($hidden_upload.length > 0) {
						$hidden_upload.addClass('do-validate');
					}
					if ($hidden_wp_editor.length > 0) {
						$hidden_wp_editor.addClass('do-validate');
					}
					if ($hidden_signature.length > 0) {
						$hidden_signature.addClass('do-validate');
					}
				}
			}

			this.$el.trigger('forminator:field:condition:toggled');

			this.toggle_confirm_password( $element_id );
		},

		clear_value: function(element_id, e) {
			var $element = this.get_form_field(element_id),
				value = this.get_field_value(element_id)
			;
			if ( $element.hasClass('forminator-cleared-value') ) {
				return;
			}
			$element.addClass('forminator-cleared-value');

			// Execute only on human action
			if (e.originalEvent !== undefined) {
				if (this.field_is_radio($element)) {
					$element.attr('data-previous-value', value);
					$element.removeAttr('checked');
				} else if (this.field_is_checkbox($element)) {
					$element.each(function () {
						if($(this).is(':checked')) {
							$(this).attr('data-previous-value', value);
						}
						$(this).removeAttr('checked');
					});
				} else {
					$element.attr('data-previous-value', value);
					$element.val('');
				}
			}
		},

		restore_value: function(element_id, e) {
			var $element = this.get_form_field(element_id),
				value = $element.attr('data-previous-value')
			;
			if ( ! $element.hasClass('forminator-cleared-value') ) {
				return;
			}

			// Execute only on human action
			if (e.originalEvent === undefined) {
				return;
			}

			$element.removeClass('forminator-cleared-value');

			// Return after class is removed if field is upload
			if ( this.field_is_upload( element_id ) ) {
				return;
			}

			if(!value) return;

			if (this.field_is_radio($element)) {
				$element.val([value]);
			} else if (this.field_is_checkbox($element)) {
				$element.each(function () {
					var value = $(this).attr('data-previous-value');

					if (!value) return;

					if (value.indexOf($(this).val()) >= 0) {
						$(this).attr("checked", "checked");
					}
				});
			} else {
				$element.val(value);
			}
		},

		hide_element: function (relation, e){
			var self = this,
				sub_relations = self.get_relations(relation);

			self.clear_value(relation, e);

			sub_relations.forEach(function (sub_relation) {
				self.toggle_field(sub_relation, 'hide', "valid");
				if (self.has_relations(sub_relation)) {
					sub_relations = self.hide_element(sub_relation, e);
				}
			});
		},

		show_element: function (relation, e){
			var self          = this,
				sub_relations = self.get_relations(relation)
            ;

			this.restore_value(relation, e);
			this.textareaFix(this.$el, relation, e);

			sub_relations.forEach(function (sub_relation) {
				var logic = self.get_field_logic(sub_relation),
					action = logic.action,
					rule = logic.rule,
					conditions = logic.conditions, // Conditions rules
					matches = 0 // Number of matches
				;

				conditions.forEach(function (condition) {
					// If rule is applicable save in matches
					if (self.is_applicable_rule(condition, action)) {
						matches++;
					}
				});

				if ((rule === "all" && matches === conditions.length) || (rule === "any" && matches > 0)) {
					self.toggle_field(sub_relation, action, "valid");
				}else{
					self.toggle_field(sub_relation, action, "invalid");
				}
				if (self.has_relations(sub_relation)) {
					sub_relations = self.show_element(sub_relation, e);
				}
			});
		},

		paypal_button_condition: function() {
			var paymentElement = this.$el.find('.forminator-paypal-row'),
				paymentPageElement = this.$el.find('.forminator-pagination-footer').find('.forminator-button-paypal');
			if( paymentElement.length > 0 ) {
				this.$el.find('.forminator-button-submit').closest('.forminator-row').removeClass('forminator-hidden');
				if( ! paymentElement.hasClass('forminator-hidden') ) {
					this.$el.find('.forminator-button-submit').closest('.forminator-row').addClass('forminator-hidden');
				}
			}
			if ( paymentPageElement.length > 0 ) {
				if( paymentPageElement.hasClass('forminator-hidden') ) {
					this.$el.find('.forminator-button-submit').removeClass('forminator-hidden');
				} else{
					this.$el.find('.forminator-button-submit').addClass('forminator-hidden');
				}
			}
		},

		maybe_clear_upload_container: function() {
			this.$el.find( '.forminator-row.forminator-hidden input[type="file"]' ).each( function () {
				if ( '' === $(this).val() ) {
					if ( $(this).parent().hasClass( 'forminator-multi-upload' ) ) {
						$(this).parent().siblings( '.forminator-uploaded-files' ).empty();
					} else {
						$(this).siblings( 'span' ).text( $(this).siblings( 'span' ).data( 'empty-text' ) );
						$(this).siblings( '.forminator-button-delete' ).hide();
					}
				}
			});
		},

        // Fixes textarea bug with labels when using Material design style
		textareaFix: function (form ,relation, e){
			var label = $( '#' + relation + ' .forminator-label' )
            ;

            if ( relation.includes( 'textarea' ) && form.hasClass( 'forminator-design--material' ) && 0 < label.length ) {
                var materialTextarea = $( '#' + relation + ' .forminator-textarea'),
                    labelPaddingTop  = label.height() + 9 // Based on forminator-form.js
                ;

                label.css({
                  'padding-top': labelPaddingTop + 'px'
                });

                materialTextarea.css({
                  'padding-top': labelPaddingTop + 'px'
                });
            }
		},

        // Maybe toggle confirm password field if necessary
		toggle_confirm_password: function ( $element ) {
			if ( 0 !== $element.length && $element.attr( 'id' ) && -1 !== $element.attr( 'id' ).indexOf( 'password' ) ) {
				var column = $element.closest( '.forminator-col' );
				if ( column.hasClass( 'forminator-hidden' ) ) {
					column.parent( '.forminator-row' ).next( '.forminator-row' ).addClass( 'forminator-hidden' );
				} else {
					column.parent( '.forminator-row' ).next( '.forminator-row' ).removeClass( 'forminator-hidden' );
				}
			}
		},
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options, calendar) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontCondition(this, options, calendar));
			}
		});
	};

})(jQuery, window, document);

// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "forminatorFrontSubmit",
		defaults = {
			form_type: 'custom-form',
			forminatorFront: false,
			forminator_selector: '',
			chart_design: 'bar',
			chart_options: {}
		};

	// The actual plugin constructor
	function ForminatorFrontSubmit(element, options) {
		this.element = element;
		this.$el = $(this.element);
		this.forminatorFront = null;


		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFrontSubmit.prototype, {
		init: function () {
			this.forminatorFront = this.$el.data('forminatorFront');
			switch (this.settings.form_type) {
				case 'custom-form':
					if (!this.settings.forminator_selector || !$(this.settings.forminator_selector).length) {
						this.settings.forminator_selector = '.forminator-custom-form';
					}
					this.handle_submit_custom_form();
					break;
				case 'quiz':
					if (!this.settings.forminator_selector || !$(this.settings.forminator_selector).length) {
						this.settings.forminator_selector = '.forminator-quiz';
					}
					this.handle_submit_quiz();
					break;
				case 'poll':
					if (!this.settings.forminator_selector || !$(this.settings.forminator_selector).length) {
						this.settings.forminator_selector = '.forminator-poll';
					}
					this.handle_submit_poll();
					break;

			}
		},

		decodeHtmlEntity: function(str) {
			return str.replace(/&#(\d+);/g, function(match, dec) {
				return String.fromCharCode(dec);
			});
		},

		handle_submit_custom_form: function () {
			var self = this,
				saveDraftBtn = self.$el.find( '.forminator-save-draft-link' );

			var success_available = self.$el.find('.forminator-response-message').find('.forminator-label--success').not(':hidden');
			if (success_available.length) {
				self.focus_to_element(self.$el.find('.forminator-response-message'));
			}
			$('.def-ajaxloader').hide();
			var isSent = false;
			$('body').on('click', '#lostPhone', function (e) {
				e.preventDefault();
				var that = $(this);
				if (isSent === false) {
					isSent = true;
					$.ajax({
						type: 'GET',
						url: that.attr('href'),
						beforeSend: function () {
							that.attr('disabled', 'disabled');
							$('.def-ajaxloader').show();
						},
						success: function (data) {
							that.removeAttr('disabled');
							$('.def-ajaxloader').hide();
							$('.notification').text(data.data.message);
							isSent = false;
						}
					})
				}
			});

			$('body').on('click', '.auth-back', function (e) {
				e.preventDefault();
				var moduleId  = self.$el.attr( 'id' ),
					authId    = moduleId + '-authentication',
					authInput = $( '#' + authId + '-input' )
				;
				authInput.attr( 'disabled','disabled' );
				FUI.closeAuthentication();
			});

			if ( 0 !== saveDraftBtn.length ) {
				this.handle_submit_form_draft();
			}

			$('body').on('submit.frontSubmit', this.settings.forminator_selector, function ( e, submitter ) {
                if ( 0 !== self.$el.find( 'input[type="hidden"][value="forminator_submit_preview_form_custom-forms"]' ).length ) {
                    return false;
                }
				var $this = $(this),
				    thisForm = this,
				    submitEvent = e,
					formData = new FormData( this ),
					$target_message = $this.find('.forminator-response-message'),
					$captcha_field = self.$el.find('.forminator-g-recaptcha, .forminator-hcaptcha'),
					$saveDraft = 'true' === self.$el.find( 'input[name="save_draft"]' ).val() ? true : false,
					$datepicker = $('body').find( '#ui-datepicker-div.forminator-custom-form-' + self.$el.data( 'form-id' ) )
					;

				if( self.settings.inline_validation && self.$el.find('.forminator-uploaded-files').length > 0 && ! $saveDraft ) {
					var file_error = self.$el.find('.forminator-uploaded-files li.forminator-has_error');
					if( file_error.length > 0 ) {
						return false;
					}
				}

				//check originalEvent exists and submit button is not exits or hidden
				if( submitEvent.originalEvent !== undefined ) {
					var submitBtn = $(this).find('.forminator-button-submit').first();
					if( submitBtn.length === 0 || $( submitBtn ).closest('.forminator-col').hasClass('forminator-hidden') ) {
						return false;
					}
				}
				// Check if datepicker is open, prevent submit
				if ( 0 !== $datepicker.length && self.$el.datepicker( "widget" ).is(":visible") ) {
					return false;
				}

				if ( self.$el.data( 'forminatorFrontPayment' ) && ! $saveDraft ) {
					// Disable submit button right away to prevent multiple submissions
					$this.find( 'button' ).attr( 'disabled', true );
					if ( false === self.processCaptcha( self, $captcha_field, $target_message ) ) {
						return false;
					}
				}

				self.multi_upload_disable( $this, true );

				var submitCallback = function() {
					var pagination 	  = self.$el.find( '.forminator-pagination:visible' ),
						hasPagination = !! pagination.length,
						formStep	  = pagination.index( '.forminator-pagination' )
						;

					formData = new FormData(this); // reinit values

					if ( $saveDraft && hasPagination ) {
						formData.append( 'draft_page', formStep );
					}

					if ( ! self.$el.data( 'forminatorFrontPayment' ) && ! $saveDraft ) {
						if ( false === self.processCaptcha( self, $captcha_field, $target_message ) ) {
							return false;
						}
					}

					// Should check if submitted thru save draft button
					if ( self.$el.hasClass('forminator_ajax') || $saveDraft ) {
						$target_message.html('');
						self.$el.find('.forminator-button-submit').addClass('forminator-button-onload');

						// Safari FIX, if empty file input, ajax broken
						// Check if input empty
						self.$el.find("input[type=file]").each(function () {
							// IE does not support FormData.delete()
							if ($(this).val() === "") {
								if (typeof(window.FormData.prototype.delete) === 'function') {
									formData.delete($(this).attr('name'));
								}
							}
						});

						var form_type = '';
						if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
							// Disable form fields
							form_type = self.$el.find('input[name="form_type"]').val();
							if( 'login' !== form_type ) {
								self.$el.addClass('forminator-fields-disabled');
							}
							$target_message.html('<p>' + self.settings.loader_label + '</p>');
							self.focus_to_element( $target_message );

							$target_message.removeAttr("aria-hidden")
								.prop("tabindex", "-1")
								.removeClass('forminator-success forminator-error forminator-accessible')
								.addClass('forminator-loading forminator-show');
						}

						e.preventDefault();
						$.ajax({
							type: 'POST',
							url: window.ForminatorFront.ajaxUrl,
							data: formData,
							cache: false,
							contentType: false,
							processData: false,
							beforeSend: function () {
								$this.find('button').attr('disabled', true);
								$this.trigger('before:forminator:form:submit', formData);
							},
							success: function( data ) {
								if( ( ! data && 'undefined' !== typeof data ) || 'object' !== typeof data.data ) {
									$this.find( 'button' ).removeAttr( 'disabled' );
									$target_message.addClass('forminator-error')
										.html( '<p>' + window.ForminatorFront.cform.error + '<br>(' + data.data + ')</p>');
									self.focus_to_element($target_message);

									return false;
								}

								// Process Save Draft's response
								if ( data.success && undefined !== data.data.type && 'save_draft' === data.data.type ) {
									self.showDraftLink( data.data );
									return false;
								}

								// Hide validation errors
								$this.find( '.forminator-error-message' ).not('.forminator-uploaded-files .forminator-error-message').remove();
								$this.find( '.forminator-field' ).removeClass( 'forminator-has_error' );

								$this.find( 'button' ).removeAttr( 'disabled' );
								$target_message.html( '' ).removeClass( 'forminator-accessible forminator-error forminator-success' );
								if( self.settings.hasLeads && 'undefined' !== typeof data.data.entry_id ) {
									self.showQuiz( self.$el );
									$('#forminator-module-' + self.settings.quiz_id + ' input[name=entry_id]' ).val( data.data.entry_id );
									if( 'end' === self.settings.form_placement ) {
										$('#forminator-module-' + self.settings.quiz_id).submit();
									}

                                    return false;
                                }
								if ( typeof data !== 'undefined' &&
									 typeof data.data !== 'undefined' &&
									 typeof data.data.authentication !== 'undefined' &&
									( 'show' === data.data.authentication || 'invalid' === data.data.authentication ) ) {
									var moduleId  = self.$el.attr( 'id' ),
										authId    = moduleId + '-authentication',
										authField = $( '#' + authId ),
										authInput = $( '#' + authId + '-input' ),
										authToken = $( '#' + authId + '-token' )
									;
									authField.find('.forminator-authentication-notice').removeClass('error');
									authField.find('.lost-device-url').attr('href', data.data.lost_url);

									if( 'show' === data.data.authentication ) {
										self.$el.find('.forminator-authentication-nav').html('').append( data.data.auth_nav );
										self.$el.find('.forminator-authentication-box').hide();
										if ( 'fallback-email' === data.data.auth_method ) {
											self.$el.find('.wpdef-2fa-email-resend input').click();
											self.$el.find('.notification').hide();
										}
										self.$el.find( '#forminator-2fa-' + data.data.auth_method ).show();
										self.$el.find('.forminator-authentication-box input').attr( 'disabled', true );
										self.$el.find( '#forminator-2fa-' + data.data.auth_method + ' input' ).attr( 'disabled', false );
										self.$el.find('.forminator-2fa-link').show();
										self.$el.find('#forminator-2fa-link-' + data.data.auth_method).hide();
										authInput.removeAttr( 'disabled' ).val(data.data.auth_method);
										authToken.val( data.data.auth_token );
										FUI.openAuthentication( authId, moduleId, authId + '-input' );
									}
									if ( 'invalid' === data.data.authentication ) {
										authField.find('.forminator-authentication-notice').addClass('error');
										authField.find('.forminator-authentication-notice').html('<p>' + data.data.message + '</p>');
									}

									return false;

								}
								var $label_class = data.success ? 'forminator-success' : 'forminator-error';

								if (typeof data.message !== "undefined") {
									$target_message.removeAttr("aria-hidden")
										.prop("tabindex", "-1")
										.addClass($label_class + ' forminator-show');
									self.focus_to_element( $target_message, false, data.fadeout, data.fadeout_time );
									$target_message.html( data.message );

									if(!data.data.success && data.data.errors.length) {
										var errors_html = '<ul class="forminator-screen-reader-only">';
										$.each(data.data.errors, function(index,value) {
											for(var propName in value) {
											    if(value.hasOwnProperty(propName)) {
											       errors_html += '<li>' + value[propName] + '</li>';
											    }
											}
										});
										errors_html += '</ul>';
										$target_message.append(errors_html);
									}
								} else {
									if (typeof data.data !== "undefined") {
										var isShowSuccessMessage = true;

										//Remove background of the success message if form behaviour is redirect and the success message is empty
										if (
											typeof data.data.url !== 'undefined' &&
											typeof data.data.newtab !== 'undefined' &&
											'newtab_thankyou' !== data.data.newtab
										) {
											isShowSuccessMessage = false;
										}
										if ( isShowSuccessMessage ) {
											$target_message.removeAttr("aria-hidden")
												.prop("tabindex", "-1")
												.addClass($label_class + ' forminator-show');
											self.focus_to_element( $target_message, false, data.data.fadeout, data.data.fadeout_time );
											$target_message.html( data.data.message );
										}

										if(!data.data.success && typeof data.data.errors !== 'undefined' && data.data.errors.length) {
											var errors_html = '<ul class="forminator-screen-reader-only">';
											$.each(data.data.errors, function(index,value) {
												//errors_html += '<li>' + value
												for(var propName in value) {
												    if(value.hasOwnProperty(propName)) {
												        errors_html += '<li>' + value[propName] + '</li>';
												    }
												}
											});
											errors_html += '</ul>';
											$target_message.append(errors_html);
										}

										if ( typeof data.data.stripe3d !== "undefined" ) {
											if ( typeof data.data.subscription !== "undefined" ) {
												$this.trigger('forminator:form:submit:stripe:3dsecurity', [ data.data.secret, data.data.subscription ]);
											} else {
												$this.trigger('forminator:form:submit:stripe:3dsecurity', [ data.data.secret, false ]);
											}
										}
									}
								}

								if ( ! data.data.success ) {
									$this.trigger('forminator:form:submit:failed', formData);
									self.multi_upload_disable( $this, false );

									if ( typeof data.data.errors !== 'undefined' && data.data.errors.length ) {
										self.show_messages(data.data.errors);
									}
								}

								if (data.success === true) {
									var hideForm = typeof data.data.behav !== "undefined" && data.data.behav === 'behaviour-hide';
									// Reset form
									if ($this[0]) {
										var resetEnabled = self.settings.resetEnabled;
										if(resetEnabled && ! hideForm) {
											$this[0].reset();
										}

										self.$el.trigger('forminator:field:condition:toggled');

										// reset signatures
										$this.find('.forminator-field-signature img').trigger('click');

										// Reset Select field submissions
										if (typeof data.data.select_field !== "undefined") {
											$.each(data.data.select_field, function (index, value) {
												if (value.length > 0) {
													$.each(value, function (i, v) {
														if (v['value']) {
															if (v['type'] === 'multiselect') {
																$this.find("#" + index + " input[value=" + v['value'] + "]").closest('.forminator-option').remove().trigger("change");
															} else {
																$this.find("#" + index + " option[value=" + v['value'] + "]").remove().trigger("change");
															}
														}
													});
												}
											});
										}
										// Reset upload field
										$this.find(".forminator-button-delete").hide();
										$this.find('.forminator-file-upload input').val("");
										$this.find('.forminator-file-upload > span').html(window.ForminatorFront.cform.no_file_chosen);
										$this.find('ul.forminator-uploaded-files').html('');
										self.$el.find('ul.forminator-uploaded-files').html('');
										self.$el.find( '.forminator-multifile-hidden' ).val('');
										//self.$el.find( '.forminator-input-file' ).val('');

										// Reset selects
										if ( $this.find('.forminator-select').length > 0 ) {
											$this.find('.forminator-select').each(function (index, value) {
												var defaultValue = $(value).data('default-value');
												if ( '' === defaultValue ) {
													defaultValue = $(value).val();
												}
												$(value).val(defaultValue).trigger("fui:change");
											});
										}
										// Reset multiselect
										$this.find('.multiselect-default-values').each(function () {
											var defaultValuesObj = '' !== $(this).val() ?  $.parseJSON( $(this).val() ) : [],
												defaultValuesArr = Object.values( defaultValuesObj ),
												multiSelect = $(this).closest('.forminator-multiselect');
											multiSelect.find('input[type="checkbox"]').each(function (i, val) {
												if( -1 !== $.inArray( $(val).val(), defaultValuesArr ) ) {
													$(val).prop('checked', true);
													$(val).closest('label').addClass('forminator-is_checked');
												} else {
													$(val).prop('checked', false);
													$(val).closest('label').removeClass('forminator-is_checked');
												}
											});
										});
										self.multi_upload_disable( $this, false );
										$this.trigger('forminator:form:submit:success', formData);

										// restart condition after form reset to ensure values of input already reset-ed too
										$this.trigger('forminator.front.condition.restart');
									}

									if (typeof data.data.url !== "undefined") {

										//check if newtab option is selected
										if(typeof data.data.newtab !== "undefined" && data.data.newtab !== "sametab"){
											if ( 'newtab_hide' === data.data.newtab ) {
												//hide if newtab redirect with hide form option selected
												self.$el.hide();
											}
											//new tab redirection
											window.open( self.decodeHtmlEntity( data.data.url ), '_blank' );
										} else {
											//same tab redirection
											window.location.href = self.decodeHtmlEntity( data.data.url );
										}

									}

									if (hideForm) {
										self.$el.find('.forminator-row').hide();
										self.$el.find('.forminator-pagination-steps').hide();
										self.$el.find('.forminator-pagination-footer').hide();
										self.$el.find('.forminator-pagination-steps, .forminator-pagination-progress').hide();
									}
								}
							},
							error: function (err) {
								if ( 0 !== saveDraftBtn.length ) {
									self.$el.find( 'input[name="save_draft"]' ).val( 'false' );
									saveDraftBtn.addClass( 'disabled' );
								}

								$this.find('button').removeAttr('disabled');
								$target_message.html('');
								var $message = err.status === 400 ? window.ForminatorFront.cform.upload_error : window.ForminatorFront.cform.error;
								$target_message.html('<label class="forminator-label--notice"><span>' + $message + '</span></label>');
								self.focus_to_element($target_message);
								$this.trigger('forminator:form:submit:failed', formData);
								self.multi_upload_disable( $this, false );
							},
							complete: function(xhr,status) {
								self.$el.find('.forminator-button-submit').removeClass('forminator-button-onload');

								$this.trigger('forminator:form:submit:complete', formData);
							}
						}).always(function () {
							if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
								// Enable form fields
								self.$el.removeClass('forminator-fields-disabled forminator-partial-disabled');

								$target_message.removeClass('forminator-loading');
							}

							if ( 0 !== saveDraftBtn.length ) {
								self.$el.find( 'input[name="save_draft"]' ).val( 'false' );
								saveDraftBtn.addClass( 'disabled' );
							}

							$this.trigger('after:forminator:form:submit', formData);
						});
					} else {
						if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
							// Disable form fields
							self.$el.addClass('forminator-fields-disabled');

							$target_message.html('<p>' + self.settings.loader_label + '</p>');

							$target_message.removeAttr("aria-hidden")
								.prop("tabindex", "-1")
								.removeClass('forminator-success forminator-error forminator-accessible')
								.addClass('forminator-loading forminator-show');
						}

						submitEvent.currentTarget.submit();
					}
				};

				// payment setup
				var paymentIsHidden = self.$el.find('div[data-is-payment="true"]')
					.closest('.forminator-row').hasClass('forminator-hidden');
				if ( self.$el.data('forminatorFrontPayment') && !paymentIsHidden && ! $saveDraft ) {
					self.$el.trigger('payment.before.submit.forminator', [formData, function () {
						submitCallback.apply(thisForm);
					}]);
				} else {
					submitCallback.apply(thisForm);
				}

				return false;
			});

		},

		handle_submit_form_draft: function () {
			var self = this;

			$('body').on( 'click', '.forminator-save-draft-link', function (e) {
				e.preventDefault();
				e.stopPropagation();

				var thisForm  = $( this ).closest( 'form' ),
					saveDraft = thisForm.find( 'input[name="save_draft"]' )
					;

				// prevent double clicks and clicking without any changes
				if (
					thisForm.closest( '#forminator-modal' ).hasClass( 'preview' ) ||
					'true' === saveDraft.val() ||
					$( this ).hasClass( 'disabled' )
				) {
					return;
				}

				saveDraft.val( 'true' );
				thisForm.trigger( 'submit.frontSubmit', 'draft_submit' );
			});

		},

		showDraftLink: function( data ) {
			var $form = this.$el;
			$form.trigger( 'forminator:form:draft:success', data );
			$form.find( '.forminator-response-message' ).html('');
			$form.hide();

			$( data.message ).insertBefore( $form );
			this.sendDraftLink( data );
		},

		sendDraftLink: function( data ) {
			var self = this,
				sendDraftForm = '#send-draft-link-form-' + data.draft_id;
				;

			$( 'body' ).on( 'submit', sendDraftForm, function(e) {
				var form 		  = $( this ),
					draftData 	  = new FormData(this),
					emailWrap 	  = form.find( '#email-1' ),
					emailField 	  = emailWrap.find( '.forminator-field' ),
					submit		  = form.find( '.forminator-button-submit' ),
					targetMessage = form.find( '.forminator-response-message' ),
					emailResponse = form.prev( '.forminator-draft-email-response' );

				if (
					$( this ).hasClass( 'submitting' ) ||
					( $( this ).hasClass( 'forminator-has_error' ) && '' === emailWrap.find( 'input[name="email-1"]' ).val() )
				) {
					return false;
				}

				// Add submitting class and disable prop to prevent multi submissions
				form.addClass( 'submitting' );
				submit.attr( 'disabled', true );

				// Reset if there's error
				form.removeClass( 'forminator-has_error' );
				emailField.removeClass( 'forminator-has_error' );
				emailField.find( '.forminator-error-message' ).remove();

				e.preventDefault();
				$.ajax({
					type: 'POST',
					url: window.ForminatorFront.ajaxUrl,
					data: draftData,
					cache: false,
					contentType: false,
					processData: false,
					beforeSend: function () {
						form.trigger( 'before:forminator:draft:email:submit', draftData );
					},
					success: function( data ) {
						var res = data.data;
						if( ( ! data && 'undefined' !== typeof data ) || 'object' !== typeof res ) {
							submit.removeAttr( 'disabled' );
							targetMessage
								.addClass( 'forminator-error' )
								.html( '<p>' + window.ForminatorFront.cform.error + '<br>(' + res + ')</p>');
							self.focus_to_element( targetMessage );

							return false;
						}

						if (
							! data.success &&
							undefined !== res.field &&
							'email-1' === res.field &&
							! emailField.hasClass( 'forminator-has_error' )
						) {
							form.addClass( 'forminator-has_error' );
							emailField.addClass( 'forminator-has_error' );
							emailField.append( '<span class="forminator-error-message" aria-hidden="true">' + res.message + '</span>' );
						}

						if ( data.success ) {
							if ( res.draft_mail_sent ) {
								emailResponse.removeClass( 'draft-error' ).addClass( 'draft-success' );
							} else {
								emailResponse.removeClass( 'draft-success' ).addClass( 'draft-error' );
							}

							emailResponse.html( res.draft_mail_message );
							emailResponse.show();
							form.hide();
						}
					},
					error: function( error ) {
						form.removeClass( 'submitting' );
						submit.removeAttr( 'disabled' );
					}
				}).always( function() {
					form.removeClass( 'submitting' );
					submit.removeAttr( 'disabled' );
				});

				emailResponse.on( 'click', '.draft-resend-mail', function( e ) {
					e.preventDefault();

					emailResponse.slideUp( 50 );
					form.show();
				});
			} );
		},

		processCaptcha: function( self, $captcha_field, $target_message ) {

			if ($captcha_field.length) {
				//validate only first
				$captcha_field = $($captcha_field.get(0));
				var captcha_size  = $captcha_field.data('size'),
					$captcha_parent = $captcha_field.parent( '.forminator-col' );

				// Recaptcha
				if ( $captcha_field.hasClass( 'forminator-g-recaptcha' ) ) {

					var captcha_widget  = $captcha_field.data( 'forminator-recapchta-widget' ),
						$captcha_response = window.grecaptcha.getResponse( captcha_widget );

					if ( captcha_size === 'invisible' ) {
						if ( $captcha_response.length === 0 ) {
							window.grecaptcha.execute( captcha_widget );
							return false;
						}
					}

					// reset after getResponse
					if ( self.$el.hasClass( 'forminator_ajax' ) ) {
						window.grecaptcha.reset(captcha_widget);
					}

				// Hcaptcha
				} else if ( $captcha_field.hasClass( 'forminator-hcaptcha' ) ) {

					var captcha_widget   = $captcha_field.data( 'forminator-hcaptcha-widget' ),
						$captcha_response = hcaptcha.getResponse( captcha_widget );

					if ( captcha_size === 'invisible' ) {
						if ( $captcha_response.length === 0 ) {
							hcaptcha.execute( captcha_widget );
							return false;
						}
					}

					// reset after getResponse
					if ( self.$el.hasClass( 'forminator_ajax' ) ) {
						hcaptcha.reset( captcha_widget );
					}
				}

				$target_message.html('');
				if ($captcha_field.hasClass("error")) {
					$captcha_field.removeClass("error");
				}

				if ($captcha_response.length === 0) {
					if (!$captcha_field.hasClass("error")) {
						$captcha_field.addClass("error");
					}

					$target_message.html('<label class="forminator-label--error"><span>' + window.ForminatorFront.cform.captcha_error + '</span></label>');

					if ( ! self.settings.inline_validation ) {
						self.focus_to_element($target_message);
					} else {

						if ( ! $captcha_parent.hasClass( 'forminator-has_error' ) && $captcha_field.data( 'size' ) !== 'invisible' ) {
							$captcha_parent.addClass( 'forminator-has_error' )
								.append( '<span class="forminator-error-message" aria-hidden="true">' + window.ForminatorFront.cform.captcha_error + '</span>' );
							self.focus_to_element( $captcha_parent );
						}

					}

					return false;
				}
			}

		},

		hideForm: function( form ) {
			form.css({
				'height': 0,
				'opacity': 0,
				'overflow': 'hidden',
				'visibility': 'hidden',
				'pointer-events': 'none',
				'margin': 0,
				'padding': 0,
				'border': 0
			});
		},

		showForm: function( form ) {
			form.css({
				'height': '',
				'opacity': '',
				'overflow': '',
				'visibility': '',
				'pointer-events': '',
				'margin': '',
				'padding': '',
				'border': ''
			});
		},

		showQuiz: function( form ) {
			var quizForm = $('#forminator-module-' + this.settings.quiz_id ),
				parent = $( '#forminator-quiz-leads-' + this.settings.quiz_id );

			this.hideForm( form );
			parent.find( '.forminator-lead-form-skip' ).hide();
			if( 'undefined' !== typeof this.settings.form_placement && 'beginning' === this.settings.form_placement ) {
				this.showForm( quizForm );
				if ( quizForm.find('.forminator-pagination').length ) {
					parent.find( '.forminator-quiz-intro').hide();
					quizForm.prepend('<button class="forminator-button forminator-quiz-start forminator-hidden"></button>')
							.find('.forminator-quiz-start').trigger('click').remove();
				}
			}
		},

		handle_submit_quiz: function( data ) {

			var self = this,
				hasLeads = 'undefined' !== typeof self.settings.hasLeads ? self.settings.hasLeads : false,
				leads_id = 'undefined' !== typeof self.settings.leads_id ? self.settings.leads_id : 0,
				quiz_id = 'undefined' !== typeof self.settings.quiz_id ? self.settings.quiz_id : 0;

			$( 'body' ).on( 'submit.frontSubmit', this.settings.forminator_selector, function( e ) {
				if ( 0 !== self.$el.find( 'input[type="hidden"][value="forminator_submit_preview_form_quizzes"]' ).length ) {
					return false;
				}
				var form       = $(this),
					ajaxData   = [],
					formData   = new FormData( this ),
					answer     = form.find( '.forminator-answer' ),
					button	   = self.$el.find('.forminator-button').last(),
					quizResult = self.$el.find( '.forminator-quiz--result' ),
					loadLabel  = button.data( 'loading' ),
					placement  = 'undefined' !== typeof self.settings.form_placement ? self.settings.form_placement : '',
					skip_form  = 'undefined' !== typeof self.settings.skip_form ? self.settings.skip_form : ''
					;

				e.preventDefault();
				e.stopPropagation();

				// Enable all inputs
				self.$el.find( '.forminator-has-been-disabled' ).removeAttr( 'disabled' );

				// Serialize fields, that should be placed here!
				ajaxData = form.serialize();

				// Disable inputs again
				self.$el.find( '.forminator-has-been-disabled' ).attr( 'disabled', 'disabled' );

				if( hasLeads ) {
					var entry_id  = '';
					if ( self.$el.find('input[name=entry_id]').length > 0 ) {
						entry_id = self.$el.find('input[name=entry_id]').val();
					}
					if( 'end' === placement && entry_id === '' ) {
						self.showForm( $('#forminator-module-' + leads_id ) );
						quizResult.addClass( 'forminator-hidden' );
						$('#forminator-quiz-leads-' + quiz_id + ' .forminator-lead-form-skip' ).show();

						return false;
					}

					if( ! skip_form && entry_id === '' ) {
						return false;
					}
				}

				// Add loading label.
				if ( loadLabel !== '' ) {
					button.text( loadLabel );
				}

				if ( self.settings.has_quiz_loader ) {
					answer.each( function() {
						var answer = $( this ),
							input  = answer.find( 'input' ),
							status = answer.find( '.forminator-answer--status' ),
							loader = '<i class="forminator-icon-loader forminator-loading"></i>'
							;

						if ( input.is( ':checked' ) ) {
							if ( 0 === status.html().length ) {
								status.html( loader );
							}
						}
					});
				}

				var pagination = !! self.$el.find('.forminator-pagination');

				$.ajax({
					type: 'POST',
					url: window.ForminatorFront.ajaxUrl,
					data: ajaxData,
					beforeSend: function() {
						if ( ! pagination ) {
							self.$el.find( 'button' ).attr( 'disabled', 'disabled' );
						}
						form.trigger( 'before:forminator:quiz:submit', [ ajaxData, formData ] );
					},
					success: function( data ) {

						if ( data.success ) {
							var resultText = '';

                            quizResult.removeClass( 'forminator-hidden' );
							window.history.pushState( 'forminator', 'Forminator', data.data.result_url );

							if ( data.data.type === 'nowrong' ) {
								resultText = data.data.result;

								quizResult.html( resultText );
								if ( ! pagination ) {
									self.$el.find( '.forminator-answer input' ).attr( 'disabled', 'disabled' );
								}

							} else if ( data.data.type === 'knowledge' ) {
								resultText = data.data.finalText;

								if ( quizResult.length > 0 ) {
									quizResult.html( resultText );
								}

								Object.keys( data.data.result ).forEach( function( key ) {

									var responseClass,
										responseIcon,
										parent  = self.$el.find( '#' + key ),
										result  = parent.find( '.forminator-question--result' ),
										submit  = parent.find( '.forminator-submit-rightaway' ),
                                        answers = parent.find( '.forminator-answer input' )
										;

									// Check if selected answer is right or wrong.
									if ( data.data.result[key].isCorrect ) {
										responseClass = 'forminator-is_correct';
										responseIcon  = '<i class="forminator-icon-check"></i>';
									} else {
										responseClass = 'forminator-is_incorrect';
										responseIcon  = '<i class="forminator-icon-cancel"></i>';
									}

									// Show question result.
									result.text( data.data.result[key].message );
									result.addClass( 'forminator-show' );
									submit.attr( 'disabled', true );
									submit.attr( 'aria-disabled', true );

                                    // Prevent user from changing answer.
                                    answers.attr( 'disabled', true );
                                    answers.attr( 'aria-disabled', true );

                                    // For multiple answers per question
                                    if ( undefined === data.data.result[key].answer ) {
                                        var answersArray = data.data.result[key].answers;

                                        for ( var $i = 0; $i < answersArray.length; $i++ ) {
                                            var answer = parent.find( '[id|="' + answersArray[$i].id + '"]' ).closest( '.forminator-answer' );

                                            // Check if selected answer is right or wrong.
                                            answer.addClass( responseClass );
                                            if ( 0 === answer.find( '.forminator-answer--status' ).html().length ) {
                                                answer.find( '.forminator-answer--status' ).html( responseIcon );
                                            } else {

                                                if ( 0 !== answer.find( '.forminator-answer--status .forminator-icon-loader' ).length ) {
                                                    answer.find( '.forminator-answer--status' ).html( responseIcon );
                                                }
                                            }
                                        }

                                    // For single answer per question
                                    } else {
                                        var answer = parent.find( '[id|="' + data.data.result[key].answer + '"]' ).closest( '.forminator-answer' );

                                        // Check if selected answer is right or wrong.
                                        answer.addClass( responseClass );
                                        if ( 0 === answer.find( '.forminator-answer--status' ).html().length ) {
                                            answer.find( '.forminator-answer--status' ).html( responseIcon );
                                        } else {

                                            if ( 0 !== answer.find( '.forminator-answer--status .forminator-icon-loader' ).length ) {
                                                answer.find( '.forminator-answer--status' ).html( responseIcon );
                                            }
                                        }
                                    }

								});
							}

							form.trigger( 'forminator:quiz:submit:success', [ ajaxData, formData, resultText ] ) ;

							if ( 0 !== quizResult.find( '.forminator-quiz--summary' ).length && ! quizResult.parent().hasClass( 'forminator-pagination--content' ) ) {
								self.focus_to_element( quizResult.find( '.forminator-quiz--summary' ) );
							}

						} else {
							self.$el.find( 'button' ).removeAttr( 'disabled' );

							form.trigger( 'forminator:quiz:submit:failed', [ ajaxData, formData ] );
						}
					}
				}).always(function () {
					form.trigger('after:forminator:quiz:submit', [ ajaxData, formData ] );
				});
				return false;
			});

			$('body').on('click', '#forminator-quiz-leads-' + quiz_id + ' .forminator-lead-form-skip', function (e) {
				self.showQuiz( $('#forminator-module-' + leads_id) );

				if( 'undefined' !== typeof self.settings.form_placement && 'end' === self.settings.form_placement ) {
					self.settings.form_placement = 'skip';
					self.$el.submit();
				}
			});

			$('body').on('click', '.forminator-result--retake', function (e) {
				var pageId = self.$el.find('input[name="page_id"]').val();
				var ajaxData = {
					action: 'forminator_reload_quiz',
					pageId:	pageId,
					nonce: self.$el.find('input[name="forminator_nonce"]').val()
				};

				e.preventDefault();

				$.post( window.ForminatorFront.ajaxUrl, ajaxData, function( response ) {
					if ( response.success == true && response.html ) {
						window.location.replace(response.html);
					}
				} );
			});
		},

		handle_submit_poll: function () {
			var self = this,
				poll_form = self.$el.html();

			// Hide (success) response message
			var success_available = self.$el.find( '.forminator-response-message' ).not( ':hidden' );

			if ( success_available.length ) {

				self.focus_to_element(
					self.$el.find( '.forminator-response-message' ),
					true
				);
			}

			$( 'body' ).on( 'submit.frontSubmit', this.settings.forminator_selector, function (e) {
				if ( 0 !== self.$el.find( 'input[type="hidden"][value="forminator_submit_preview_form_poll"]' ).length ) {
					return false;
				}
				var $this    = $( this ),
					formData  = new FormData( this ),
					ajaxData  = $this.serialize()
				;

				var $response = self.$el.find( '.forminator-response-message' ),
					$options  = self.$el.find( 'fieldset' ),
					$submit   = self.$el.find( '.forminator-button' )
				;

				function response_clean() {
					// Remove content
					$response.html( '' );

					// Remove all classes
					$response.removeClass( 'forminator-show' );
					$response.removeClass( 'forminator-error' );
					$response.removeClass( 'forminator-success' );

					// Hide for screen readers
					$response.removeAttr( 'tabindex' );
					$response.attr( 'aria-hidden', true );

					// Remove options error class
					$options.removeClass( 'forminator-has_error' );

				}

				function response_message( message, custom_class ) {

					// Print message
					$response.html( '<p>' + message + '</p>' );

					// Add necessary classes
					$response.addClass( 'forminator-' + custom_class );
					$response.addClass( 'forminator-show' );

					// Show for screen readers
					$response.removeAttr( 'aria-hidden' );
					$response.attr( 'tabindex', '-1' );

					// Focus message
					$response.focus();

					// Add options error class
					if ( 'error' === custom_class ) {

						if ( ! $options.find( 'input[type="radio"]' ).is( ':checked' ) ) {
							$options.addClass( 'forminator-has_error' );
						}
					}
				}

				if ( self.$el.hasClass( 'forminator_ajax' ) ) {
					response_clean();

					$.ajax({
						type: 'POST',
						url:  window.ForminatorFront.ajaxUrl,
						data: ajaxData,

						beforeSend: function() {

							// Animate "submit" button
							$submit.addClass( 'forminator-onload' );

							// Trigger "submit" action
							$this.trigger( 'before:forminator:poll:submit', [ ajaxData, formData ] );

						},

						success: function( data ) {

							var $label_class = data.success ? 'success' : 'error';

							// Stop "submit" animation
							$submit.removeClass( 'forminator-onload' );

							if ( false === data.success ) {

								// Print message
								response_message( data.data.message, $label_class );

								// Failed response
								$this.trigger( 'forminator:poll:submit:failed', [ ajaxData, formData ] );

							} else {

								if ( 'undefined' !== typeof data.data ) {

									$label_class = data.data.success ? 'success' : 'error';

									// Print message
									response_message( data.data.message, $label_class );

									// Auto close message
									setTimeout( function() {
										response_clean();
									}, 2500 );

								}
							}

							if ( true === data.success ) {

								if ( typeof data.data.url !== 'undefined' ) {
									window.location.href = data.data.url;
								} else {

									// url not exist, it will render chart on the fly if chart_data exist on response
									// check length is > 1, because [0] is header
									if ( typeof data.data.chart_data !== 'undefined' && data.data.chart_data.length > 1 ) {

										if ( 'link_on' === data.data.results_behav ) {

											if ( $this.find( '.forminator-note' ).length ) {
												$this.find( '.forminator-note' ).remove();
												$this.find( '.forminator-poll-footer' ).append( data.data.results_link );
											}
										}

										if ( 'show_after' === data.data.results_behav ) {

											self.render_poll_chart(
												data.data.chart_data,
												data.data.back_button,
												self,
												poll_form,
												[
													data.data.votes_text,
													data.data.votes_count,
													[
														data.data.grids_color,
														data.data.labels_color,
														data.data.onchart_label
													],
													[
														data.data.tooltips_bg,
														data.data.tooltips_color
													]
												]
											);

										}
									}
								}

								// Success response
								$this.trigger( 'forminator:poll:submit:success', [ ajaxData, formData ] );

							}
						},

						error: function() {

							response_clean();

							// Stop "submit" animation
							$submit.removeClass( '.forminator-onload' );

							// Failed response
							$this.trigger( 'forminator:poll:submit:failed', [ ajaxData, formData ] );

						}
					}).always( function() {

						$this.trigger( 'after:forminator:poll:submit', [ ajaxData, formData ] );

					});

					return false;

				}

				return true;

			});
		},

		render_poll_chart: function( chart_data, back_button, forminatorSubmit, poll_form, chart_extras ) {
			var pollId      = forminatorSubmit.$el.attr( 'id' ) + '-' + forminatorSubmit.$el.data('forminatorRender'),
				chartId     = 'forminator-chart-poll-' + pollId,
				pollBody    = forminatorSubmit.$el.find( '.forminator-poll-body' ),
				pollFooter  = forminatorSubmit.$el.find( '.forminator-poll-footer' )
				;

			function chart_clean() {

				var canvas = forminatorSubmit.$el.find( '.forminator-chart-wrapper' ),
					wrapper = forminatorSubmit.$el.find( '.forminator-chart' )
					;

				canvas.remove();
				wrapper.remove();

			}

			function chart_create() {
				var canvas = $( '<canvas id="' + chartId + '" class="forminator-chart" role="img" aria-hidden="true"></canvas>' );

				pollBody.append( canvas );
			}

			function chart_show() {
				var canvas = forminatorSubmit.$el.find( '.forminator-chart' ),
					wrapper = forminatorSubmit.$el.find( '.forminator-chart-wrapper' )
					;

				if ( wrapper.length ) {

					// Show canvas
					canvas.addClass( 'forminator-show' );

					// Show wrapper
					wrapper.addClass( 'forminator-show' );
					wrapper.removeAttr( 'aria-hidden' );
					wrapper.attr( 'tabindex', '-1' );

					// Focus message
					wrapper.focus();
				} else {
					// Fallback text
					canvas.html( '<p>Fallback text...</p>' );

					// Show canvas
					canvas.addClass( 'forminator-show' );
					canvas.removeAttr( 'aria-hidden' );
					canvas.attr( 'tabindex', '-1' );

					// Focus message
					canvas.focus();
				}
			}

			function hide_answers() {
				var answers = pollBody.find( '.forminator-field' );

				answers.hide();
				answers.attr( 'aria-hidden', 'true' );
			}

			function replace_footer() {

				var button = $( back_button );

				pollFooter.empty();
				pollFooter.append( button );

			}

			function back_to_poll() {

				var button = forminatorSubmit.$el.find( '.forminator-button' );

				button.click( function( e ) {

					if ( forminatorSubmit.$el.hasClass( 'forminator_ajax' ) ) {
						forminatorSubmit.$el.html( poll_form );
					} else {
						location.reload();
					}

					e.preventDefault();

				});
			}

			// Remove previously chart if exists
			chart_clean();

			// Create chart markup
			chart_create();

			// Load chart
			FUI.pollChart(
				'#' + chartId,
				chart_data,
				forminatorSubmit.settings.chart_design,
				chart_extras
			);

			// Hide poll answers
			hide_answers();

			// Show poll chart
			chart_show();

			// Replace footer
			replace_footer();
			back_to_poll();

		},

		focus_to_element: function ( $element, not_scroll, fadeout, fadeout_time ) {
			fadeout = fadeout || false;
			fadeout_time = fadeout_time || 0;
			not_scroll = not_scroll || false;
			var parent_selector = 'html,body';

			// check inside sui modal
			if ( $element.closest( '.sui-dialog' ).length > 0 ) {
				parent_selector = '.sui-dialog';
			}

			// check inside hustle modal (prioritize)
			if ( $element.closest( '.wph-modal' ).length > 0 ) {
				parent_selector = '.wph-modal';
			}

			// if element is not forminator textarea, force show in case its hidden of fadeOut
			if ( ! $element.hasClass( 'forminator-textarea' ) && ! $element.parent( '.wp-editor-container' ).length ) {
				$element.show();
			} else if ( $element.hasClass( 'forminator-textarea' ) && $element.parent( '.wp-editor-container' ).length ) {
				$element = $element.parent( '.wp-editor-container' );
			}

			function focusElement( $element ) {
				if ( ! $element.attr("tabindex") ) {
					$element.attr("tabindex", -1);
				}

				if ( ! $element.hasClass( 'forminator-select2' ) ) {
					$element.focus();
				}

				if (fadeout) {
					$element.show().delay( fadeout_time ).fadeOut('slow');
				}
			}

			if ( not_scroll ) {
				focusElement($element);
			} else {
				$( parent_selector ).animate({scrollTop: ($element.offset().top - ($(window).height() - $element.outerHeight(true)) / 2)}, 500, function () {
					focusElement($element);
				});
			}
		},

		show_messages: function( errors ) {
			var self = this,
				forminatorFrontCondition = self.$el.data('forminatorFrontCondition');
			if (typeof forminatorFrontCondition !== 'undefined') {

				// clear all validation message before show new one
				this.$el.find('.forminator-error-message').remove();
				var i = 0;

				errors.forEach( function( value ) {

					var elementId  = Object.keys( value ),
						getElement = forminatorFrontCondition.get_form_field( elementId )
						;

					var holder      = $( getElement ),
						holderField = holder.closest( '.forminator-field' ),
						holderDate  = holder.closest( '.forminator-date-input' ),
						holderTime  = holder.closest( '.forminator-timepicker' ),
						holderError = '',
						getColumn   = false,
						getError    = false,
						getDesc     = false
						;

					var errorMessage = Object.values( value ),
						errorMarkup  = '<span class="forminator-error-message" aria-hidden="true"></span>'
						;

					if ( getElement.length ) {

						// Focus on first error
						if ( i === 0 ) {
							self.$el.trigger( 'forminator.front.pagination.focus.input', [getElement]);
							self.focus_to_element( getElement );
						}

						// CHECK: Timepicker field.
						if ( holderDate.length > 0 ) {

							getColumn = holderDate.parent();
							getError  = getColumn.find( '.forminator-error-message[data-error-field="' + holder.data( 'field' ) + '"]' );
							getDesc   = getColumn.find( '.forminator-description' );

							errorMarkup = '<span class="forminator-error-message" data-error-field="' + holder.data( 'field' ) + '" aria-hidden="true"></span>';

							if ( 0 === getError.length ) {

								if ( 'day' === holder.data( 'field' ) ) {

									if ( getColumn.find( '.forminator-error-message[data-error-field="year"]' ).length ) {

										$( errorMarkup ).insertBefore( getColumn.find( '.forminator-error-message[data-error-field="year"]' ) );

									} else {

										if ( 0 === getDesc.length ) {
											getColumn.append( errorMarkup );
										} else {
											$( errorMarkup ).insertBefore( getDesc );
										}
									}

									if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

										holderField.append(
											'<span class="forminator-error-message" aria-hidden="true"></span>'
										);
									}
								}

								if ( 'month' === holder.data( 'field' ) ) {

									if ( getColumn.find( '.forminator-error-message[data-error-field="day"]' ).length ) {

										$( errorMarkup ).insertBefore(
											getColumn.find( '.forminator-error-message[data-error-field="day"]' )
										);

									} else {

										if ( 0 === getDesc.length ) {
											getColumn.append( errorMarkup );
										} else {
											$( errorMarkup ).insertBefore( getDesc );
										}
									}

									if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

										holderField.append(
											'<span class="forminator-error-message" aria-hidden="true"></span>'
										);
									}
								}

								if ( 'year' === holder.data( 'field' ) ) {

									if ( 0 === getDesc.length ) {
										getColumn.append( errorMarkup );
									} else {
										$( errorMarkup ).insertBefore( getDesc );
									}

									if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

										holderField.append(
											'<span class="forminator-error-message" aria-hidden="true"></span>'
										);
									}
								}
							}

							holderError = getColumn.find( '.forminator-error-message[data-error-field="' + holder.data( 'field' ) + '"]' );

							// Insert error message
							holderError.html( errorMessage );
							holderField.find( '.forminator-error-message' ).html( errorMessage );

						} else if ( holderTime.length > 0 && errorMessage[0].length > 0 ) {

							getColumn = holderTime.parent();
							getError  = getColumn.find( '.forminator-error-message[data-error-field="' + holder.data( 'field' ) + '"]' );
							getDesc   = getColumn.find( '.forminator-description' );

							errorMarkup = '<span class="forminator-error-message" data-error-field="' + holder.data( 'field' ) + '" aria-hidden="true"></span>';

							if ( 0 === getError.length ) {

								if ( 'hours' === holder.data( 'field' ) ) {

									if ( getColumn.find( '.forminator-error-message[data-error-field="minutes"]' ).length ) {

										$( errorMarkup ).insertBefore(
											getColumn.find( '.forminator-error-message[data-error-field="minutes"]' )
										);
									} else {

										if ( 0 === getDesc.length ) {
											getColumn.append( errorMarkup );
										} else {
											$( errorMarkup ).insertBefore( getDesc );
										}
									}

									if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

										holderField.append(
											'<span class="forminator-error-message" aria-hidden="true"></span>'
										);
									}
								}

								if ( 'minutes' === holder.data( 'field' ) ) {

									if ( 0 === getDesc.length ) {
										getColumn.append( errorMarkup );
									} else {
										$( errorMarkup ).insertBefore( getDesc );
									}

									if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

										holderField.append(
											'<span class="forminator-error-message" aria-hidden="true"></span>'
										);
									}
								}
							}

							holderError = getColumn.find( '.forminator-error-message[data-error-field="' + holder.data( 'field' ) + '"]' );

							// Insert error message
							holderError.html( errorMessage );
							holderField.find( '.forminator-error-message' ).html( errorMessage );

						} else {

							var getError = holderField.find( '.forminator-error-message' ),
								getDesc  = holderField.find( '.forminator-description' )
								;

							if ( 0 === getError.length ) {

								if ( 0 === getDesc.length ) {
									holderField.append( errorMarkup );
								} else {
									$( errorMarkup ).insertBefore( getDesc );
								}
							}

							holderError = holderField.find( '.forminator-error-message' );

							// Insert error message
							holderError.html( errorMessage );

						}

						// Field invalid status for screen readers
						holder.attr( 'aria-invalid', 'true' );

						// Field error status
						holderField.addClass( 'forminator-has_error' );

						i++;

					}
				});
			}

			return this;
		},

		multi_upload_disable: function ( $form, disable ) {
			$form.find( '.forminator-multi-upload input' ).each( function() {
				var file_method = $(this).data('method');
				if( 'ajax' === file_method ) {
					$(this).attr('disabled', disable);
				}
			});
		}

	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontSubmit(this, options));
			}
		});
	};

})(jQuery, window, document);

// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "forminatorFrontMultiFile",
		defaults = {};

	// The actual plugin constructor
	function ForminatorFrontMultiFile(element, options) {
		this.element = element;
		this.$el = $(this.element);

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.form = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;
		this.form_id = 0;
		this.uploader = this.$el;
		this.element = this.uploader.data('element');

		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFrontMultiFile.prototype, {
		init: function () {
			var self = this,
				fileList = [],
				ajax_request = [];

			if (this.form.find('input[name=form_id]').length > 0) {
				this.form_id = this.form.find('input[name=form_id]').val();
			}

			this.uploader.on("drag dragstart dragend dragover dragenter dragleave drop", function(e) {
				e.preventDefault();
				e.stopPropagation();
			});
			this.uploader.on("dragover dragenter", function(a) {
				$(this).addClass("forminator-dragover");
			});
			this.uploader.on("dragleave dragend drop", function(a) {
				$(this).removeClass("forminator-dragover");
			});
			this.uploader.find( ".forminator-upload-file--forminator-field-" + this.element ).on("click", function(e) {
				self.form.find( '.forminator-field-' + self.element + '-' + self.form_id ).click();
			});

			this.uploader.on("drop", function(e) {
				document.querySelector( '.forminator-field-' + self.element + '-' + self.form_id ).files = e.originalEvent.dataTransfer.files;
				self.form.find( '.forminator-field-' + self.element + '-' + self.form_id ).change();
			});

			this.uploader.on("click", function(e) {
				if ( e.target === e.currentTarget ) {
					self.form.find( '.forminator-field-' + self.element + '-' + self.form_id ).click();
				}
			});
			this.uploader.find('.forminator-multi-upload-message, .forminator-multi-upload-message p, .forminator-multi-upload-message .forminator-icon-upload').on("click", function(e) {
				if ( e.target === e.currentTarget ) {
					self.form.find( '.forminator-field-' + self.element + '-' + self.form_id ).click();
				}
			});

			this.form.on("forminator:form:submit:success", function(e) {
				fileList = [];
			});
			this.form.find( '.forminator-field-' + self.element + '-' + self.form_id ).on("change", function(e) {
				if( ! self.uploadingFile ){
					self.uploadingFile = 1;

					var $this = $(this),
						param = this.files,
						uploadParam = [];

					$.when().then(function(){
						$this.closest('.forminator-field').removeClass('forminator-has_error');
						for ( var i = 0; i < param.length; i++ ) {
							uploadParam.push( param[ i ] );
							fileList.push( param[ i ] );
						}

						ajax_request = self.handleChangeCallback( uploadParam, $this, ajax_request );
						var file_list = Array.prototype.slice.call( fileList );

						if ( file_list.length > 0 ) {
							param = self.FileObjectItem(file_list);	
							if ( 'submission' === $this.data( 'method' ) ) {
								$this.prop( 'files', param );
							} 
						}
					}).done(function(){
						self.uploadingFile = null;
					});
				}
			});

			this.delete_files( fileList, ajax_request );
		},

		/**
		 * Upload Ajax call
		 *
		 * @param param
		 * @param $this
		 * @param ajax_request
		 */
		handleChangeCallback: function ( param, $this, ajax_request ) {
			var self = this,
				ajax_inc = 0,
				uploadData = new FormData,
				nonce = this.form.find('input[name="forminator_nonce"]').val(),
				method = $this.data('method');
			uploadData.append( "action", "forminator_multiple_file_upload" );
			uploadData.append( "form_id", this.form_id );
			uploadData.append( "element_id", self.element );
			uploadData.append( "nonce", nonce );
			$.each( param, function ( i, item ) {
				var unique_id = self.progress_bar( item, method ),
					totalFile = self.form.find('.upload-container-' + self.element + ' li').length,
					fileType = 'undefined' !== typeof $this.data('filetype') ? $this.data('filetype') : '',
					file_reg = new RegExp("(.*?)\.("+ fileType +")$"),
					itemName = item.name.toLowerCase();
				if ( 'undefined' !== typeof $this.data('size') && $this.data('size') <= item.size ) {
					error_messsage = $this.data('size-message');
					self.upload_fail_response( unique_id, error_messsage );
					return;
				} else if( ! file_reg.test( itemName ) ) {
					var ext = itemName.split('.').pop();
					error_messsage = '.' + ext + ' ' + $this.data('filetype-message');
					self.upload_fail_response( unique_id, error_messsage );
					return;
				}
				if( 'ajax' === method ) {
					uploadData.delete( self.element );
					uploadData.delete( 'totalFiles' );
					uploadData.append( "totalFiles", totalFile );
					uploadData.append( self.element, item );
					ajax_request.push( $.ajax({
						xhr: function () {
							var xhr = new window.XMLHttpRequest();
							xhr.upload.addEventListener("progress", function (evt) {
								if (evt.lengthComputable) {
									var percentComplete = ( ( evt.loaded / evt.total ) * 100 );
									if( 90 > percentComplete ) {
										self.form.find('#' + unique_id + ' .progress-percentage')
											.html(Math.round(percentComplete) + '% of ');
									}
								}
							}, false);
							return xhr;
						},
						type: 'POST',
						url: window.ForminatorFront.ajaxUrl,
						data: uploadData,
						cache: false,
						contentType: false,
						processData: false,
						beforeSend: function () {
							self.form.find('.forminator-button-submit').attr( 'disabled', true );
							self.$el.trigger('before:forminator:multiple:upload', uploadData);
						},
						success: function (data) {
							var element = self.element,
								current_file = {
									success: data.success,
									message: 'undefined' !== data.data.message ? data.data.message : '',
									file_id: unique_id,
									file_name: 'undefined' !== typeof data.data.file_url ? data.data.file_url.replace(/^.*[\\\/]/, '') : item.name,
									mime_type: item.type,
								};
							self.add_upload_file( element, current_file );
							if ( true === data.success && true === data.data.success && 'undefined' !== typeof data.data ) {
								self.upload_success_response( unique_id );
								self.$el.trigger('success:forminator:multiple:upload', uploadData);
							} else {
								self.upload_fail_response( unique_id, data.data.message );
								if( 'undefined' !== typeof data.data.error_type && 'limit' === data.data.error_type ) {
									self.form.find('#' + unique_id).addClass('forminator-upload-limit_error');
								}
								self.$el.trigger('fail:forminator:multiple:upload', uploadData);
							}
						},
						complete: function (xhr, status) {
							ajax_inc++;
							if ( param.length === ajax_inc ) {
								self.form.find('.forminator-button-submit').attr( 'disabled', false );
							}
							self.$el.trigger('complete:forminator:multiple:upload', uploadData);
						},
						error: function (err) {
							self.upload_fail_response( unique_id, window.ForminatorFront.cform.process_error );
						}
					}))
				} else {
					var has_error = true,
						error_messsage = window.ForminatorFront.cform.process_error;

					if( 'undefined' !== typeof $this.data('limit') && $this.data('limit') < totalFile ) {
						has_error = false;
						self.form.find('#' + unique_id).addClass('forminator-upload-limit_error');
						error_messsage = $this.data('limit-message');
					}

					if( ! has_error ) {
						self.upload_fail_response( unique_id, error_messsage );

					} else {
						self.upload_success_response( unique_id );
					}
				}
			});

			return ajax_request;
		},

		/**
		 * Ajax fail response
		 *
		 * @param unique_id
		 * @param message
		 */
		upload_fail_response: function( unique_id, message ) {
			this.form.find('#' + unique_id).addClass('forminator-has_error');
			this.form.find('#' + unique_id).find('.forminator-uploaded-file--size [class*="forminator-icon-"]')
				.addClass('forminator-icon-warning')
				.removeClass('forminator-icon-loader')
				.removeClass('forminator-loading');
			this.form.find('#' + unique_id + ' .progress-percentage').html('0% of ');
			this.form.find('#' + unique_id + ' .forminator-uploaded-file--content')
				.after('<div class="forminator-error-message">' + message + '</div>');
		},

		/**
		 * Ajax success response
		 *
		 * @param unique_id
		 */
		upload_success_response: function( unique_id ) {
			this.form.find('#' + unique_id + ' .progress-percentage').html('100% of ');
			this.form.find('#' + unique_id + ' .forminator-uploaded-file--size [class*="forminator-icon-"]').remove();
			this.form.find('#' + unique_id + ' .progress-percentage').remove();
		},

		/**
		 * Show progress bar
		 *
		 * @param file
		 * @param method
		 */
		progress_bar: function( file, method ) {
			var self = this,
				uniqueID = Math.random().toString( 36 ).substr( 2, 7 ),
				uniqueId = 'upload-process-' + uniqueID,
				filename = file.name,
				filesize = self.bytes_to_size( file.size, 2 ),
				wrapper  = this.uploader.closest( '.forminator-field' ).find( '.forminator-uploaded-files' ),
				markup   = ''
			;

			this.progress_image_preview( file, uniqueId );

			function getFileExtension( element ) {
				var parts = element.split( '.' );
				return parts[ parts.length - 1 ];
			}

			function isImage( element ) {

				var ext = getFileExtension( element );

				switch ( ext.toLowerCase() ) {
					case 'jpg':
					case 'jpe':
					case 'jpeg':
					case 'png':
					case 'gif':
					case 'ico':
						return true;
				}

				return false;

			}

			/**
			 * File Preview Markup.
			 *
			 * Get the icon file or replace it with image preview.
			 */
			var preview = '<div class="forminator-uploaded-file--preview" aria-hidden="true">' +
				'<span class="forminator-icon-file" aria-hidden="true"></span>' +
				'</div>';

			if ( isImage( filename ) ) {
				preview = '<div class="forminator-uploaded-file--image" aria-hidden="true">' +
					'<div class="forminator-img-preview" role="image"></div>' +
					'</div>';
			}

			/**
			 * File Name.
			 *
			 * Get the name of the uploaded file (extension included).
			 */

			var name = '<p class="forminator-uploaded-file--title">' + filename + '</p>';

			/**
			 * File Size.
			 *
			 * Depending on the state of the file user will get a:
			 * - Loading Icon: When file is still being uploaded.
			 *   This will be accompanied by percent amount.
			 * - Warning Icon: When file finished loading but an
			 *   error happened.
			 * - File Size.
			 */

			var size = '<p class="forminator-uploaded-file--size">' +
				'<span class="forminator-icon-loader forminator-loading" aria-hidden="true"></span>' +
				'<span class="progress-percentage">29% of </span>' +
				filesize +
				'</p>';

			/**
			 * File Delete Button.
			 *
			 * This icon button will have the ability to remove
			 * the uploaded file.
			 */

			var trash = '<button type="button" class="forminator-uploaded-file--delete forminator-button-delete" data-method="' + method + '" data-element="' + self.element + '" data-value="' + uniqueId + '">' +
				'<span class="forminator-icon-close" aria-hidden="true"></span>' +
				'<span class="forminator-screen-reader-only">Delete uploaded file</span>' +
				'</button>';

			/**
			 * Markup.
			 */

			markup += '<li id="' + uniqueId + '" class="forminator-uploaded-file">';
			markup += '<div class="forminator-uploaded-file--content">';
			markup += preview;
			markup += '<div class="forminator-uploaded-file--text">';
			markup += name;
			markup += size;
			markup += '</div>';
			markup += trash;
			markup += '</div>';
			markup += '</li>';

			/**
			 * Has Files Class.
			 *
			 * Add "forminator-has-files" class to wrapper.
			 */

			if ( ! wrapper.hasClass( '.forminator-has-files' ) ) {
				wrapper.addClass( 'forminator-has-files' );
			}

			return wrapper.append( markup ), uniqueId;

		},

		bytes_to_size: function ( bytes, decimals ) {

			if ( 0 === bytes ) return '0 Bytes';

			var k = 1024,
				dm = decimals < 0 ? 0 : decimals,
				sizes = [ 'Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ],
				i = Math.floor( Math.log( bytes ) / Math.log( k ) );

			return parseFloat( ( bytes / Math.pow( k, i ) ).toFixed( dm ) ) + ' ' + sizes[ i ];
		},

		/**
		 * image preview
		 *
		 * @param image
		 * @param uniqueId
		 */
		progress_image_preview: function ( image, uniqueId ) {
			if ( image ) {
				var reader = new FileReader();
				reader.onload = function (e) {
					$('#'+ uniqueId + ' .forminator-img-preview').css('background-image', 'url(' + e.target.result + ')');
				};
				reader.readAsDataURL(image);
			}
		},

		/**
		 * Get all uploaded file
		 *
		 * @returns {*}
		 */
		get_uplaoded_files: function () {
			var uploaded_value = this.form.find( '.forminator-multifile-hidden' ), files;

			files = uploaded_value.val();
			files = ( typeof files === "undefined" ) || files === '' ? {} : $.parseJSON( files );

			return files;
		},

		/**
		 * Get file by element
		 *
		 * @param element
		 * @returns {*}
		 */
		get_uplaoded_file: function ( element ) {
			var uploaded_file = this.get_uplaoded_files();

			if( typeof uploaded_file[ element ] === 'undefined' )
				uploaded_file[ element ] = [];

			return uploaded_file[ element ];
		},

		/**
		 * Add uploaded file
		 *
		 * @param element
		 * @param response
		 */
		add_upload_file: function ( element, response ) {
			var files = this.get_uplaoded_file( element );

			files.unshift( response );
			this.set_upload_file( element, files );
		},

		/**
		 * Set upload file
		 *
		 * @param element
		 * @param files
		 */
		set_upload_file: function ( element, files ) {
			var upload_file = this.get_uplaoded_files(),
				uploaded_value = this.form.find( '.forminator-multifile-hidden' );
			upload_file[ element ] = files;
			uploaded_value.val( JSON.stringify( upload_file ) );
		},

		/**
		 * Get uploaded by file id
		 *
		 * @param element
		 * @param file_id
		 * @returns {*}
		 */
		get_uploaded_file_id: function ( element, file_id ) {
			var file_index = null,
				upload_file = this.get_uplaoded_file( element );
			$.each( upload_file, function ( key, val ) {
				if( file_id === val['file_id'] ) file_index = key;
			});

			return file_index;
		},

		/**
		 * Delete files
		 */
		delete_files: function ( fileList, ajax_request ) {
			var self = this;
			$( document ).on( "click", ".forminator-uploaded-file--delete", function( e ) {
				e.preventDefault();
				var deleteButton = $( this ),
					file_id = deleteButton.data('value'),
					method = deleteButton.data('method'),
					element_id = deleteButton.data('element');
				if( 'undefined' !== typeof file_id && 'undefined' !== typeof element_id && 'undefined' !== typeof method ) {

					var index = self.form.find('#' + file_id ).index(),
						fileContainer = $( deleteButton ).closest( 'li#' + file_id ),
						uploaded_arr = self.get_uplaoded_files(),
						uploaded_value = self.form.find( '.forminator-multifile-hidden' );

					if ( uploaded_arr && 'ajax' === method ) {

						if( 'undefined' !== typeof ajax_request[ index ] ) {
							ajax_request[ index ].abort();
							ajax_request.splice( index, 1 );
						}
						if( 'undefined' !== typeof uploaded_value ) {
							var file_index = self.get_uploaded_file_id( element_id, file_id );
							if( '' !== file_index && null !== file_index ) {
								uploaded_arr[ element_id ].splice( file_index, 1 );
							}
							uploaded_value.val( JSON.stringify( uploaded_arr ) );
						}
					}

					if( 'undefined' !== typeof method && 'submission' === method ) {
						self.remove_object( index, fileList, element_id );
					}

					$( fileContainer ).remove();
				}
				var fileInput = self.form.find( '.forminator-field-'+ self.element + '-' + self.form_id );
				var liList = self.form.find('.upload-container-' + element_id + ' li' );
				if( 'undefined' !== typeof fileInput.data('limit') ) {
					$.each( liList,function( index ) {
						if( fileInput.data('limit') > index && $(this).hasClass('forminator-upload-limit_error') ) {
							var fileID = $(this).attr('id'),
								fileIndex = self.get_uploaded_file_id( element_id, fileID );
							$(this).removeClass('forminator-has_error');
							$(this).find('.forminator-error-message, .forminator-icon-warning, .progress-percentage').remove();
							if( '' !== fileIndex && null !== fileIndex && 'undefined' !== typeof uploaded_arr[ element_id ][ fileIndex ] ) {
								uploaded_arr[ element_id ][ fileIndex ].success = true;
							}
						}
					});
					uploaded_value.val( JSON.stringify( uploaded_arr ) );
				}

				// empty file input value if no files left
				if ( liList.length === 0 ) {
					fileInput.val('');
				}
			})
		},

		remove_object: function( index, fileList, element_id ) {
			var upload_input = document.querySelector( '.forminator-field-'+ element_id + '-' + this.form_id );
			if( 'undefined' !== typeof upload_input ) {
				var	upload_files = upload_input.files;
				if( upload_files.length > 0 ) {
					var upload_slice = Array.prototype.slice.call( upload_files );
					fileList.splice( index, 1 );
					upload_slice.splice( index, 1 );
					upload_input.files = this.FileObjectItem( upload_slice );
				}
			}
		},

		/**
		 * File list object
		 *
		 * @param a
		 * @returns {FileList}
		 * @constructor
		 */
		FileObjectItem: function ( a ) {
			a = [].slice.call( Array.isArray( a ) ? a : arguments );
			a = a.reverse();
			for ( var c, b = c = a.length, d = !0; b-- && d; ) {
				d = a[ b ] instanceof File;
			}
			if ( ! d ) throw new TypeError("expected argument to FileList is File or array of File objects");
			for ( b = ( new ClipboardEvent("") ).clipboardData || new DataTransfer; c--;) {
				b.items.add( a[ c ] );
			}

			return b.files
		}
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontMultiFile(this, options));
			}
		});
	};

})(jQuery, window, document);
