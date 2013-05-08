package at.ac.fhs.rs1.task4.algorithm.mve;

import java.util.Vector;

import Jama.Matrix;
import Jama.SingularValueDecomposition;


/**
 * 
 * @author Markus Deutschl <mdeutschl.mmt-m2012@fh-salzburg.ac.at>
 *
 */
public class MVEAlgorithm {

	private Matrix gramMatrix;
	
	public MVEAlgorithm(Matrix gramMatrix) {
		this.gramMatrix = gramMatrix;
	}
	
	public Vector<Double> process(int d, double beta, double kappa) {
		Vector<Double> result = new Vector<Double>();
		Matrix KHat = this.gramMatrix.copy();
		double B = 0.0d;
		do{
			SingularValueDecomposition svd = this.gramMatrix.svd();
			// TODO Solve eigenvectors, eigenvalues and lambdas
			// TODO Calculate B
			this.gramMatrix = KHat.copy();
			// TODO Calculate new KHat by SDP with argmax
		}while(this.gramMatrix.minus(KHat).norm2() > kappa);
		// TODO Perform SVD on KHat: compute d eigenvectors + eigenvalues
		// TODO Compute y-vector values as sqrt(lambda^i * vHat^i)
		
		return result;
	}
}
