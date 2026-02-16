<?php

namespace App\Services;

class XmlRpcClient
{
    private string $endpoint;
    private int $timeout;

    public function __construct(string $endpoint, int $timeout = 15)
    {
        $this->endpoint = $endpoint;
        $this->timeout = $timeout;
    }

    public function call(string $method, array $params = [])
    {
        $xml = $this->buildRequest($method, $params);

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: text/xml\r\n",
                'content' => $xml,
                'timeout' => $this->timeout,
            ],
        ]);

        $response = @file_get_contents($this->endpoint, false, $ctx);
        if ($response === false) {
            $err = error_get_last();
            throw new \RuntimeException('XML-RPC request failed: ' . json_encode($err));
        }

        return $this->decodeResponse($response);
    }

    private function buildRequest(string $method, array $params): string
    {
        $out = "<?xml version=\"1.0\"?>\n";
        $out .= '<methodCall>';
        $out .= '<methodName>' . htmlspecialchars($method, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</methodName>';
        $out .= '<params>';
        foreach ($params as $p) {
            $out .= '<param><value>' . $this->encodeValue($p) . '</value></param>';
        }
        $out .= '</params>';
        $out .= '</methodCall>';
        return $out;
    }

    private function encodeValue($value): string
    {
        if ($value === null) {
            return '<nil/>';
        }

        if (is_bool($value)) {
            return '<boolean>' . ($value ? '1' : '0') . '</boolean>';
        }

        if (is_int($value)) {
            return '<int>' . $value . '</int>';
        }

        if (is_float($value)) {
            return '<double>' . $value . '</double>';
        }

        if (is_string($value)) {
            return '<string>' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</string>';
        }

        if (is_array($value)) {
            if ($this->isAssoc($value)) {
                $out = '<struct>';
                foreach ($value as $k => $v) {
                    $out .= '<member>';
                    $out .= '<name>' . htmlspecialchars((string)$k, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</name>';
                    $out .= '<value>' . $this->encodeValue($v) . '</value>';
                    $out .= '</member>';
                }
                $out .= '</struct>';
                return $out;
            }

            $out = '<array><data>';
            foreach ($value as $v) {
                $out .= '<value>' . $this->encodeValue($v) . '</value>';
            }
            $out .= '</data></array>';
            return $out;
        }

        return '<string>' . htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</string>';
    }

    private function decodeResponse(string $xml)
    {
        $sx = @simplexml_load_string($xml);
        if ($sx === false) {
            throw new \RuntimeException('Invalid XML-RPC response');
        }

        if (isset($sx->fault)) {
            $fault = $this->decodeValueNode($sx->fault->value);
            $msg = is_array($fault) ? ($fault['faultString'] ?? 'XML-RPC fault') : 'XML-RPC fault';
            throw new \RuntimeException((string)$msg);
        }

        if (!isset($sx->params->param->value)) {
            return null;
        }

        return $this->decodeValueNode($sx->params->param->value);
    }

    private function decodeValueNode($valueNode)
    {
        if ($valueNode === null) {
            return null;
        }

        $children = $valueNode->children();
        if (count($children) === 0) {
            return (string)$valueNode;
        }

        $typeNode = $children[0];
        $typeName = $typeNode->getName();

        switch ($typeName) {
            case 'int':
            case 'i4':
                return (int)$typeNode;
            case 'double':
                return (float)$typeNode;
            case 'boolean':
                return ((string)$typeNode) === '1';
            case 'string':
                return (string)$typeNode;
            case 'nil':
                return null;
            case 'array':
                $out = [];
                if (isset($typeNode->data->value)) {
                    foreach ($typeNode->data->value as $v) {
                        $out[] = $this->decodeValueNode($v);
                    }
                }
                return $out;
            case 'struct':
                $out = [];
                if (isset($typeNode->member)) {
                    foreach ($typeNode->member as $m) {
                        $name = (string)$m->name;
                        $out[$name] = $this->decodeValueNode($m->value);
                    }
                }
                return $out;
            default:
                return (string)$typeNode;
        }
    }

    private function isAssoc(array $arr): bool
    {
        $i = 0;
        foreach ($arr as $k => $_) {
            if ($k !== $i) {
                return true;
            }
            $i++;
        }
        return false;
    }
}
