import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  publicDir: false, // Disable copying public dir content
  build: {
    outDir: 'public/build', 
    emptyOutDir: true,
    rollupOptions: {
      input: 'resources/js/game.jsx',
      output: {
        entryFileNames: `assets/[name].js`,
        chunkFileNames: `assets/[name].js`,
        assetFileNames: `assets/[name].[ext]`
      },
      // Prevent issues with circular dependencies
      maxParallelFileOps: 20
    },
  },
  server: {
    watch: {
      // Explicitly ignore the build output directory
      ignored: ['**/public/build/**']
    }
  }
});