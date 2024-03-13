import sys
import argparse
import re
from enum import Enum
import xml.dom.minidom as XDOM

class OperandTypes(Enum):
    VAR = 'var'
    SYMB = 'symb'
    TYPE = 'type'
    LABEL = 'label'

class ErrorCodes(Enum):
    ERR_PARAMETER = 10
    ERR_INPUT = 11
    ERR_OUTPUT = 12
    ERR_HEADER = 21
    ERR_SRC_CODE = 22
    ERR_SYNTAX = 23
    ERR_INTERNAL = 99
    EXIT_SUCCESS = 0

class ErrorHandler:
    @staticmethod
    def exit_with_error(err_code, message):
        sys.stderr.write(f'\033[31m[{err_code.name}]\033[0m {message}({err_code.value}) \n')
        sys.exit(err_code.value)

class Validators:
    @staticmethod
    def is_header(line):
        return line.lower() == '.ippcode24'

    @staticmethod
    def is_var(var):
        if '@' not in var:
            return False
        frame, name = var.split('@')
        if frame not in ['GF', 'TF', 'LF']:
            return False
        return Validators.is_label(name)

    @staticmethod
    def is_symb(symb):
        if '@' not in symb:
            return False
        type, literal = symb.split('@')
        if not Validators.is_type(type):
            return Validators.is_var(symb)
        if type == 'int':
            return re.match(r'^([-+]?\d+)|(0[oO]?[0-7]+)|(0[xX][0-9a-fA-F]+)$', literal) is not None
        elif type == 'bool':
            return re.match(r'^(true|false)$', literal) is not None
        elif type == 'nil':
            return re.match(r'^nil$', literal) is not None
        elif type == 'string':
            return not re.match(r'.*(\\(?!\d\d\d)).*|u', literal)
        return True

    @staticmethod
    def is_type(type):
        return re.match(r'^(int|bool|string|nil)$', type) is not None

    @staticmethod
    def is_label(label):
        return re.match(r'^[a-zA-Z_\-$&%*!?][\w\-$&%*!?]*$', label) is not None
    
class InstructionRule:
    def __init__(self, *operand_types):
        self.operands = list(operand_types)

    def get_operands(self):
        return self.operands

    def has_no_operands(self):
        return bool(self.operands)

INSTRUCTION_RULES = {
    'move': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB),
    'createframe': InstructionRule(),
    'pushframe': InstructionRule(),
    'popframe': InstructionRule(),
    'defvar': InstructionRule(OperandTypes.VAR),
    'call': InstructionRule(OperandTypes.LABEL),
    'return': InstructionRule(),
    'pushs': InstructionRule(OperandTypes.SYMB),
    'pops': InstructionRule(OperandTypes.VAR),
    'add': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB, OperandTypes.SYMB),
    'sub': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB, OperandTypes.SYMB),
    'mul': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB, OperandTypes.SYMB),
    'idiv': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB, OperandTypes.SYMB),
    'lt': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB, OperandTypes.SYMB),
    'gt': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB, OperandTypes.SYMB),
    'eq': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB, OperandTypes.SYMB),
    'and': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB, OperandTypes.SYMB),
    'or': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB, OperandTypes.SYMB),
    'not': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB),
    'int2char': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB),
    'stri2int': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB, OperandTypes.SYMB),
    'read': InstructionRule(OperandTypes.VAR, OperandTypes.TYPE),
    'write': InstructionRule(OperandTypes.SYMB),
    'concat': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB, OperandTypes.SYMB),
    'strlen': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB),
    'getchar': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB, OperandTypes.SYMB),
    'setchar': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB, OperandTypes.SYMB),
    'type': InstructionRule(OperandTypes.VAR, OperandTypes.SYMB),
    'label': InstructionRule(OperandTypes.LABEL),
    'jump': InstructionRule(OperandTypes.LABEL),
    'jumpifeq': InstructionRule(OperandTypes.LABEL, OperandTypes.SYMB, OperandTypes.SYMB),
    'jumpifneq': InstructionRule(OperandTypes.LABEL, OperandTypes.SYMB, OperandTypes.SYMB),
    'exit': InstructionRule(OperandTypes.SYMB),
    'dprint': InstructionRule(OperandTypes.SYMB),
    'break': InstructionRule(),
}

class Formatter:
    def format_line(self, line):
        if not isinstance(line, str):
            return False
        line = self.remove_ending(line)
        line = self.remove_comments(line)
        line = self.remove_empty(line)
        return line

    def remove_ending(self, line):
        return line.rstrip('\n')

    def remove_comments(self, line):
        cut_line = line.find('#')
        if cut_line == -1:
            return line.strip()
        return line[:cut_line].strip()

    def remove_empty(self, line):
        if not line.strip():
            return None
        return line
    
class InputReader(Formatter):
    def __init__(self):
        self.input = []

    def get_input(self):
        try:
            for line in sys.stdin:
                formatted_line = self.format_line(line)
                if formatted_line:
                    self.input.append(formatted_line)
        except:       
            return self.input
        return self.input
    
class Operand:
    def __init__(self, operand, type):
        if type == OperandTypes.SYMB:
            if Validators.is_var(operand):
                self.type, self.value = 'var', operand
            elif Validators.is_symb(operand):
                self.type, self.value = operand.split('@')
            else:
                # Handle other cases if needed
                pass
        elif type in (OperandTypes.VAR, OperandTypes.TYPE, OperandTypes.LABEL):
            self.type = type.value
            self.value = operand


class Instruction:
    general_order = 1

    def __init__(self, destructured_line):
        self.name = destructured_line.pop(0).lower()
        self.operands = []

        if self.name not in INSTRUCTION_RULES:
            ErrorHandler.exit_with_error(ErrorCodes.ERR_SRC_CODE, 'instruction does not exist')

        instruction_rule = INSTRUCTION_RULES[self.name]

        if len(destructured_line) != len(instruction_rule.get_operands()):
            ErrorHandler.exit_with_error(ErrorCodes.ERR_SYNTAX, 'Invalid number of operands')

        for key, operand in enumerate(instruction_rule.get_operands()):
            if operand == OperandTypes.VAR:
                if not Validators.is_var(destructured_line[key]):
                    ErrorHandler.exit_with_error(ErrorCodes.ERR_SYNTAX, 'Invalid variable')
            elif operand == OperandTypes.SYMB:
                if not Validators.is_symb(destructured_line[key]):
                    ErrorHandler.exit_with_error(ErrorCodes.ERR_SYNTAX, 'Invalid constant')
            elif operand == OperandTypes.TYPE:
                if not Validators.is_type(destructured_line[key]):
                    ErrorHandler.exit_with_error(ErrorCodes.ERR_SYNTAX, 'Invalid type')
            elif operand == OperandTypes.LABEL:
                if not Validators.is_label(destructured_line[key]):
                    ErrorHandler.exit_with_error(ErrorCodes.ERR_SYNTAX, 'Invalid label')
            else:
                continue

            self.operands.append(Operand(destructured_line[key], operand))

        self.order = Instruction.general_order
        Instruction.general_order += 1

    def get_name(self):
        return self.name.upper()

    def get_operands(self):
        return self.operands

    def get_order(self):
        return self.order
    
class InputAnalyser:
    def __init__(self, input_data):
        self.input = input_data
        self.instructions = []

    def get_instructions(self):
        if len(self.input) == 0:
            ErrorHandler.exit_with_error(ErrorCodes.ERR_HEADER, 'invalid header')
        if not Validators.is_header(self.input[0]):
            ErrorHandler.exit_with_error(ErrorCodes.ERR_HEADER, 'invalid header')

        self.input.pop(0)

        for line in self.input:
            self.instructions.append(Instruction(line.split()))

        return self.instructions

class XMLGenerator:
    def __init__(self):
        self.output = XDOM.Document()
        self.program = self.output.createElement('program')
        self.program.setAttribute('language', 'IPPcode24')
        self.output.appendChild(self.program)

    def generate_instruction(self, instruction):
        instruction_template = self.output.createElement('instruction')
        instruction_template.setAttribute('order', str(instruction.get_order()))
        instruction_template.setAttribute('opcode', instruction.get_name())

        for key, operand in enumerate(instruction.get_operands(), 1):
            operand_template = self.output.createElement(f'arg{key}')
            operand_template.setAttribute('type', operand.type.lower())
            operand_template.appendChild(self.output.createTextNode(operand.value))
            instruction_template.appendChild(operand_template)

        self.program.appendChild(instruction_template)

class OutputGenerator(XMLGenerator):
    def __init__(self, instructions):
        super().__init__()
        self.instructions = instructions

    def generate(self):
        for instruction in self.instructions:
            self.generate_instruction(instruction)

        return self.output.toprettyxml(encoding='UTF-8').decode('utf-8')

# Command-line arguments parsing
parser = argparse.ArgumentParser(description='IPPCode24 parser')
args = parser.parse_args()

reader = InputReader()
input_data = reader.get_input()

input_analyser = InputAnalyser(input_data)
instructions = input_analyser.get_instructions()

output_generator = OutputGenerator(instructions)
output = output_generator.generate()
print(output)