/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([], function () {
    'use strict';

    /**
     * Build LCS (Longest Common Subsequence) table for two arrays of lines
     *
     * @param {string[]} oldLines
     * @param {string[]} newLines
     * @return {number[][]}
     */
    function buildLcsTable(oldLines, newLines) {
        var m = oldLines.length,
            n = newLines.length,
            table = [],
            i, j;

        for (i = 0; i <= m; i++) {
            table[i] = [];

            for (j = 0; j <= n; j++) {
                if (i === 0 || j === 0) {
                    table[i][j] = 0;
                } else if (oldLines[i - 1] === newLines[j - 1]) {
                    table[i][j] = table[i - 1][j - 1] + 1;
                } else {
                    table[i][j] = Math.max(table[i - 1][j], table[i][j - 1]);
                }
            }
        }

        return table;
    }

    /**
     * Backtrack LCS table to produce diff operations
     *
     * @param {number[][]} table
     * @param {string[]} oldLines
     * @param {string[]} newLines
     * @return {Array<{type: string, oldLine: number|null, newLine: number|null, text: string}>}
     */
    function backtrack(table, oldLines, newLines) {
        var result = [],
            i = oldLines.length,
            j = newLines.length;

        while (i > 0 || j > 0) {
            if (i > 0 && j > 0 && oldLines[i - 1] === newLines[j - 1]) {
                result.push({type: 'equal', oldLine: i, newLine: j, text: oldLines[i - 1]});
                i--;
                j--;
            } else if (j > 0 && (i === 0 || table[i][j - 1] >= table[i - 1][j])) {
                result.push({type: 'add', oldLine: null, newLine: j, text: newLines[j - 1]});
                j--;
            } else {
                result.push({type: 'remove', oldLine: i, newLine: null, text: oldLines[i - 1]});
                i--;
            }
        }

        result.reverse();

        return result;
    }

    /**
     * Group diff operations into hunks with context lines
     *
     * @param {Array<{type: string, oldLine: number|null, newLine: number|null, text: string}>} ops
     * @param {number} contextSize
     * @return {Array<{lines: Array<{type: string, oldLine: number|null, newLine: number|null, text: string}>}>}
     */
    function groupIntoHunks(ops, contextSize) {
        var hunks = [],
            changeIndices = [],
            i, start, end, hunk, currentHunk;

        for (i = 0; i < ops.length; i++) {
            if (ops[i].type !== 'equal') {
                changeIndices.push(i);
            }
        }

        if (changeIndices.length === 0) {
            return [];
        }

        currentHunk = null;

        for (i = 0; i < changeIndices.length; i++) {
            start = Math.max(0, changeIndices[i] - contextSize);
            end = Math.min(ops.length - 1, changeIndices[i] + contextSize);

            if (currentHunk && start <= currentHunk.end + 1) {
                currentHunk.end = end;
            } else {
                if (currentHunk) {
                    hunks.push(currentHunk);
                }

                currentHunk = {start: start, end: end};
            }
        }

        if (currentHunk) {
            hunks.push(currentHunk);
        }

        return hunks.map(function (h) {
            return {
                lines: ops.slice(h.start, h.end + 1)
            };
        });
    }

    /**
     * Compute diff between two text strings
     *
     * @param {string} oldText
     * @param {string} newText
     * @param {number} [contextSize=3]
     * @return {Array<{lines: Array<{type: string, oldLine: number|null, newLine: number|null, text: string}>}>}
     */
    function computeDiff(oldText, newText, contextSize) {
        var oldLines = (oldText || '').split('\n'),
            newLines = (newText || '').split('\n'),
            table, ops;

        if (typeof contextSize === 'undefined') {
            contextSize = 3;
        }

        table = buildLcsTable(oldLines, newLines);
        ops = backtrack(table, oldLines, newLines);

        return groupIntoHunks(ops, contextSize);
    }

    return {
        computeDiff: computeDiff
    };
});
