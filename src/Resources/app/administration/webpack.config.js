const { join, resolve } = require('path'); 
module.exports = () => { 
    return { 
        resolve: { 
           alias: { 
               '@mime-types': resolve( 
                    join(__dirname, '..', 'node_modules', 'mime-types') 
               ) 
           } 
       } 
   }; 
}