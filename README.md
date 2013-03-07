di-wrapper
==========

A Zend Framework 2 module that uses auto-generated factory code for dependency-injection.

This wrapper module for Zend\Di\Di has the following features:
- DI definition scanning and factory code generation
- Can deal with shared instances
- Can be used as a fallback abstract factory for Zend\ServiceManager
- Detects outdated generated code and automatic rescanning (great for development)
- Can create new instances or reuse instances created before
